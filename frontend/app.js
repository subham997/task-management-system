angular.module('taskApp', ['ngRoute'])
  .constant('APP_CONFIG', {
    apiBase: '/api'
  })
  .config(function ($routeProvider, $locationProvider) {
    $locationProvider.hashPrefix('');

    $routeProvider
      .when('/login', {
        templateUrl: 'views/login.html',
        controller: 'LoginCtrl'
      })
      .when('/dashboard', {
        templateUrl: 'views/dashboard.html',
        controller: 'DashboardCtrl',
        authRequired: true
      })
      .when('/tasks', {
        templateUrl: 'views/tasks.html',
        controller: 'TaskListCtrl',
        authRequired: true
      })
      .when('/tasks/:id', {
        templateUrl: 'views/task-detail.html',
        controller: 'TaskDetailCtrl',
        authRequired: true
      })
      .when('/my-tasks', {
        templateUrl: 'views/my-tasks.html',
        controller: 'MyTasksCtrl',
        authRequired: true
      })
      .when('/assignment-history', {
        templateUrl: 'views/assignment-history.html',
        controller: 'AssignmentHistoryCtrl',
        authRequired: true
      })
      .otherwise({ redirectTo: '/dashboard' });
  })
  .run(function ($rootScope, $location, AuthService) {
    AuthService.bootstrap();

    $rootScope.$on('$routeChangeStart', function (event, next) {
      if (next.$$route && next.$$route.authRequired && !AuthService.isAuthenticated()) {
        event.preventDefault();
        $location.path('/login');
      }

      if (next.$$route && next.$$route.originalPath === '/login' && AuthService.isAuthenticated()) {
        event.preventDefault();
        $location.path('/dashboard');
      }
    });

    $rootScope.logout = function () {
      AuthService.logout();
      $location.path('/login');
    };

    $rootScope.isAuthenticated = function () {
      return AuthService.isAuthenticated();
    };

    $rootScope.$watch(function () {
      return AuthService.user;
    }, function (user) {
      $rootScope.currentUser = user;
    });
  })
  .filter('range', function () {
    return function (input, total) {
      total = parseInt(total, 10);
      for (var i = 0; i < total; i++) {
        input.push(i);
      }
      return input;
    };
  })
  .factory('AuthService', function ($http, $q, APP_CONFIG, $window) {
    var service = {
      user: null,
      token: null,
      bootstrap: function () {
        var storedToken = $window.localStorage.getItem('task_token');
        var storedUser = $window.localStorage.getItem('task_user');

        if (storedToken) {
          this.token = storedToken;
        }

        if (storedUser) {
          this.user = JSON.parse(storedUser);
        }
      },
      isAuthenticated: function () {
        return !!this.token;
      },
      login: function (credentials) {
        return $http.post(APP_CONFIG.apiBase + '/auth/login', credentials).then(function (response) {
          var data = response.data.data || {};
          service.token = data.token;
          service.user = data.user;
          $window.localStorage.setItem('task_token', data.token);
          $window.localStorage.setItem('task_user', JSON.stringify(data.user));
          return response;
        });
      },
      logout: function () {
        var deferred = $q.defer();
        $http.post(APP_CONFIG.apiBase + '/auth/logout', {}, {
          headers: {
            Authorization: 'Bearer ' + service.token
          }
        }).finally(function () {
          service.token = null;
          service.user = null;
          $window.localStorage.removeItem('task_token');
          $window.localStorage.removeItem('task_user');
          deferred.resolve();
        });
        return deferred.promise;
      },
      getUser: function () {
        return $http.get(APP_CONFIG.apiBase + '/auth/me').then(function (response) {
          service.user = response.data.data;
          $window.localStorage.setItem('task_user', JSON.stringify(service.user));
          return response;
        });
      }
    };

    return service;
  })
  .factory('ApiService', function ($http, AuthService, APP_CONFIG, $q) {
    function request(method, endpoint, params, data) {
      var config = {
        method: method,
        url: APP_CONFIG.apiBase + endpoint,
        params: params || {},
        data: data || {}
      };

      if (AuthService.isAuthenticated()) {
        config.headers = {
          Authorization: 'Bearer ' + AuthService.token
        };
      }

      return $http(config).then(function (response) {
        return response.data;
      }, function (response) {
        return $q.reject(response);
      });
    }

    return {
      get: function (endpoint, params) {
        return request('GET', endpoint, params);
      },
      post: function (endpoint, data) {
        return request('POST', endpoint, null, data);
      },
      put: function (endpoint, data) {
        return request('PUT', endpoint, null, data);
      },
      delete: function (endpoint) {
        return request('DELETE', endpoint);
      }
    };
  })
  .controller('LoginCtrl', function ($scope, $location, AuthService) {
    $scope.credentials = { email: '', password: '' };
    $scope.loading = false;
    $scope.error = null;

    $scope.submit = function () {
      $scope.loading = true;
      $scope.error = null;

      AuthService.login($scope.credentials).then(function () {
        $location.path('/dashboard');
      }, function () {
        $scope.error = 'Unable to sign in. Please verify your credentials.';
      }).finally(function () {
        $scope.loading = false;
      });
    };
  })
  .controller('DashboardCtrl', function ($scope, ApiService, AuthService) {
    $scope.loading = true;
    $scope.queueStatus = { status: 'Checking…' };
    $scope.stats = {};

    function loadDashboard() {
      $scope.loading = true;
      ApiService.get('/tasks', { per_page: 5, sort_by: 'updated_at', sort_direction: 'desc' })
        .then(function (response) {
          $scope.tasks = response.data || [];
          $scope.stats.total = response.meta && response.meta.total ? response.meta.total : response.data.length;
        })
        .catch(function () {
          $scope.error = 'Unable to load tasks right now.';
        })
        .finally(function () {
          $scope.loading = false;
        });

      ApiService.get('/auth/me').then(function (response) {
        AuthService.user = response.data;
      }).catch(function () {
        $scope.error = 'Unable to refresh your profile.';
      });

      ApiService.get('/queue-status').then(function (response) {
        $scope.queueStatus = response || { status: 'Healthy' };
      }).catch(function () {
        $scope.queueStatus = { status: 'Unavailable', detail: 'Queue status endpoint is not exposed by the current API.' };
      });
    }

    loadDashboard();
  })
  .controller('TaskListCtrl', function ($scope, ApiService) {
    $scope.filters = { search: '', status: '', priority: '', sort_by: 'updated_at', sort_direction: 'desc', page: 1, per_page: 10 };
    $scope.loading = true;

    $scope.loadTasks = function () {
      $scope.loading = true;
      var params = {
        search: $scope.filters.search || undefined,
        status: $scope.filters.status || undefined,
        priority: $scope.filters.priority || undefined,
        sort_by: $scope.filters.sort_by,
        sort_direction: $scope.filters.sort_direction,
        per_page: $scope.filters.per_page,
        page: $scope.filters.page
      };

      ApiService.get('/tasks', params).then(function (response) {
        $scope.tasks = response.data || [];
        $scope.pagination = response.meta || {};
      }).catch(function () {
        $scope.error = 'Unable to load tasks.';
      }).finally(function () {
        $scope.loading = false;
      });
    };

    $scope.changePage = function (page) {
      $scope.filters.page = page;
      $scope.loadTasks();
    };

    $scope.loadTasks();
  })
  .controller('TaskDetailCtrl', function ($scope, $routeParams, ApiService) {
    $scope.loading = true;
    $scope.task = null;

    ApiService.get('/tasks/' + $routeParams.id).then(function (response) {
      $scope.task = response.data && response.data.task ? response.data.task : response.data;
      $scope.assignmentLogs = $scope.task.assignment_logs || [];
    }).catch(function () {
      $scope.error = 'Unable to load task details.';
    }).finally(function () {
      $scope.loading = false;
    });
  })
  .controller('MyTasksCtrl', function ($scope, ApiService, AuthService) {
    $scope.loading = true;

    ApiService.get('/tasks', { per_page: 20, sort_by: 'updated_at', sort_direction: 'desc' }).then(function (response) {
      $scope.tasks = (response.data || []).filter(function (task) {
        return task.creator && AuthService.user && task.creator.id === AuthService.user.id;
      });
    }).catch(function () {
      $scope.error = 'Unable to load your tasks.';
    }).finally(function () {
      $scope.loading = false;
    });
  })
  .controller('AssignmentHistoryCtrl', function ($scope, ApiService) {
    $scope.loading = true;

    ApiService.get('/tasks', { per_page: 20, sort_by: 'updated_at', sort_direction: 'desc' }).then(function (response) {
      $scope.history = response.data || [];
    }).catch(function () {
      $scope.error = 'Unable to load assignment history.';
    }).finally(function () {
      $scope.loading = false;
    });
  });
