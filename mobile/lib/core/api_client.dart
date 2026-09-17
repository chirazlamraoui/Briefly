import 'package:dio/dio.dart';

import '../models/models.dart';
import 'api_config.dart';
import 'api_exception.dart';
import 'token_storage.dart';

/// Calls the same URLs as the website (`routes/web.php`).
/// Always asks for JSON. Attaches the saved login token on every request.
class ApiClient {
  ApiClient({required this._tokenStorage, Dio? dio})
      : _dio = dio ??
            Dio(
              BaseOptions(
                baseUrl: ApiConfig.baseUrl,
                headers: {
                  'Accept': 'application/json',
                  'Content-Type': 'application/json',
                },
                validateStatus: (status) => status != null && status < 500,
              ),
            ) {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _tokenStorage.read();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
      ),
    );
  }

  final TokenStorage _tokenStorage;
  final Dio _dio;

  Future<({String token, AppUser user})> login(String email, String password) async {
    final data = await _send('POST', '/login', body: {
      'email': email,
      'password': password,
      'device_name': 'flutter',
    });

    return (
      token: data['token'] as String,
      user: AppUser.fromJson(_asMap(data['user'])),
    );
  }

  Future<void> logout() async {
    await _send('POST', '/logout', allowEmpty: true);
  }

  Future<void> forgotPassword(String email) async {
    await _send('POST', '/forgot-password', body: {'email': email});
  }

  Future<void> resetPassword({
    required String token,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    await _send('POST', '/reset-password', body: {
      'token': token,
      'email': email,
      'password': password,
      'password_confirmation': passwordConfirmation,
    });
  }

  Future<AppUser> profile() async {
    final data = await _send('GET', '/profile');
    return AppUser.fromJson(_asMap(data['user'] ?? data['data'] ?? data));
  }

  Future<AppUser> updateProfile({required String name, required String email}) async {
    final data = await _send('PUT', '/profile', body: {'name': name, 'email': email});
    return AppUser.fromJson(_asMap(data['user'] ?? data['data']));
  }

  Future<void> updatePassword({
    required String currentPassword,
    required String password,
    required String passwordConfirmation,
  }) async {
    await _send('PUT', '/profile/password', body: {
      'current_password': currentPassword,
      'password': password,
      'password_confirmation': passwordConfirmation,
    });
  }

  Future<DashboardData> dashboard() async {
    final data = await _send('GET', '/dashboard');
    final progress = _asMap(data['personal_progress']);
    final tasks = (progress['tasks'] as List? ?? []).whereType<Map<String, dynamic>>().map((row) {
      return AppTask.fromJson(_asMap(row['task'] ?? row));
    }).toList();

    final ledTeams = (data['led_teams'] as List? ?? []).whereType<Map<String, dynamic>>().map((row) {
      final progressMap = _asMap(row['progress']);
      return LedTeamSection(
        team: AppTeam.fromJson(_asMap(row['team'])),
        stats: Map<String, int>.from(
          (_asMap(row['stats'])).map((key, value) => MapEntry(key, value as int? ?? 0)),
        ),
        memberRates: mapJsonList(progressMap['member_rates'], RateRow.fromJson),
        projectRates: mapJsonList(progressMap['project_rates'], RateRow.fromJson),
        blockedTasks: mapJsonList(row['blocked_tasks'], AppTask.fromJson),
      );
    }).toList();

    return DashboardData(
      overallRate: progress['overall_rate'] as int? ?? 0,
      doneCount: progress['done_count'] as int? ?? 0,
      totalCount: progress['total_count'] as int? ?? 0,
      tasks: tasks,
      ledTeams: ledTeams,
    );
  }

  Future<List<AppTask>> tasks() async {
    final data = await _send('GET', '/tasks');
    return mapJsonList(_laravelList(data), AppTask.fromJson);
  }

  Future<PaginatedUpdates> taskHistory() async {
    final data = await _send('GET', '/tasks/history');
    return PaginatedUpdates(items: mapJsonList(_laravelList(data), AppTaskUpdate.fromJson));
  }

  Future<({AppTask task, bool canUpdateProgress, bool canEdit})> task(int id) async {
    final data = await _send('GET', '/tasks/$id');
    return (
      task: AppTask.fromJson(_asMap(data['task'])),
      canUpdateProgress: data['can_update_progress'] == true,
      canEdit: data['can_edit'] == true,
    );
  }

  Future<AppTask> updateProgress(
    int id, {
    required String status,
    String? progressDone,
    String? progressNext,
    String? blockerNote,
  }) async {
    final data = await _send('PATCH', '/tasks/$id/progress', body: {
      'status': status,
      'progress_done': progressDone,
      'progress_next': progressNext,
      'blocker_note': blockerNote,
    });

    return AppTask.fromJson(_asMap(data['task']));
  }

  Future<List<AppProject>> projects() async {
    final data = await _send('GET', '/projects');
    return mapJsonList(_laravelList(data), AppProject.fromJson);
  }

  Future<({AppProject project, List<AppTask> tasks, List<AppUser> members})> project(int id) async {
    final data = await _send('GET', '/projects/$id');
    return (
      project: AppProject.fromJson(_asMap(data['project'])),
      tasks: mapJsonList(data['tasks'], AppTask.fromJson),
      members: mapJsonList(data['members'], AppUser.fromJson),
    );
  }

  Future<({AppProject project, List<AppUser> members})> taskCreateForm(int projectId) async {
    final data = await _send('GET', '/projects/$projectId/tasks/create');
    return (
      project: AppProject.fromJson(_asMap(data['project'])),
      members: mapJsonList(data['members'], AppUser.fromJson),
    );
  }

  Future<AppTask> createTask(
    int projectId, {
    required String title,
    String? description,
    required int assignedTo,
    required String status,
  }) async {
    final data = await _send('POST', '/projects/$projectId/tasks', body: {
      'title': title,
      'description': description,
      'assigned_to': assignedTo,
      'status': status,
    });

    return AppTask.fromJson(_asMap(data['data'] ?? data));
  }

  Future<({AppTask task, List<AppUser> members})> taskEditForm(int taskId) async {
    final data = await _send('GET', '/tasks/$taskId/edit');
    return (
      task: AppTask.fromJson(_asMap(data['task'])),
      members: mapJsonList(data['members'], AppUser.fromJson),
    );
  }

  Future<AppTask> updateTask(
    int taskId, {
    required String title,
    String? description,
    required int assignedTo,
    required String status,
  }) async {
    final data = await _send('PUT', '/tasks/$taskId', body: {
      'title': title,
      'description': description,
      'assigned_to': assignedTo,
      'status': status,
    });

    return AppTask.fromJson(_asMap(data['task'] ?? data['data']));
  }

  Future<({Map<String, int> stats, List<({AppTask task, AppTeam team})> tasks})> teamTasks({String? status}) async {
    final data = await _send('GET', '/team/tasks', query: {'status': ?status});
    final rows = (data['tasks'] as List? ?? []).whereType<Map<String, dynamic>>().map((row) {
      return (task: AppTask.fromJson(_asMap(row['task'])), team: AppTeam.fromJson(_asMap(row['team'])));
    }).toList();

    return (
      stats: Map<String, int>.from((_asMap(data['stats'])).map((key, value) => MapEntry(key, value as int? ?? 0))),
      tasks: rows,
    );
  }

  Future<({AppUser user, List<AppTask> tasks, PaginatedUpdates updates})> teamMember(int userId) async {
    final data = await _send('GET', '/team/members/$userId');

    return (
      user: AppUser.fromJson(_asMap(data['user'])),
      tasks: mapJsonList(data['tasks'], AppTask.fromJson),
      updates: PaginatedUpdates(items: mapJsonList(data['updates'], AppTaskUpdate.fromJson)),
    );
  }

  Future<AdminDashboardData> adminDashboard() async {
    final data = await _send('GET', '/admin');
    final completion = _asMap(data['completion']);
    final chart = _asMap(completion['status_chart']);

    return AdminDashboardData(
      completionRate: completion['overall_rate'] as int? ?? 0,
      statusLabels: (chart['labels'] as List? ?? []).map((item) => item.toString()).toList(),
      statusValues: (chart['values'] as List? ?? []).map((item) => item as int).toList(),
      statusColors: (chart['colors'] as List? ?? []).map((item) => item.toString()).toList(),
      teamRates: mapJsonList(completion['team_rates'], RateRow.fromJson),
      projectRates: mapJsonList(completion['project_rates'], RateRow.fromJson),
    );
  }

  Future<List<AppUser>> adminUsers() async {
    final data = await _send('GET', '/admin/users');
    return mapJsonList(_laravelList(data), AppUser.fromJson);
  }

  Future<List<AppTeam>> adminUserFormTeams() async {
    final data = await _send('GET', '/admin/users/create');
    return mapJsonList(data['teams'], AppTeam.fromJson);
  }

  Future<AppUser> createUser(Map<String, dynamic> body) async {
    final data = await _send('POST', '/admin/users', body: body);
    return AppUser.fromJson(_asMap(data['data'] ?? data));
  }

  Future<({AppUser user, List<AppTeam> teams, List<int> selectedTeamIds, List<int> selectedTeamLeadIds})>
      adminUser(int id) async {
    final data = await _send('GET', '/admin/users/$id');
    return (
      user: AppUser.fromJson(_asMap(data['user'])),
      teams: mapJsonList(data['teams'], AppTeam.fromJson),
      selectedTeamIds: _intList(data['selected_team_ids']),
      selectedTeamLeadIds: _intList(data['selected_team_lead_ids']),
    );
  }

  Future<AppUser> updateUser(int id, Map<String, dynamic> body) async {
    final data = await _send('PUT', '/admin/users/$id', body: body);
    return AppUser.fromJson(_asMap(data['user'] ?? data['data']));
  }

  Future<List<AppTeam>> adminTeams() async {
    final data = await _send('GET', '/admin/teams');
    return mapJsonList(_laravelList(data), AppTeam.fromJson);
  }

  Future<List<AppUser>> adminTeamFormUsers() async {
    final data = await _send('GET', '/admin/teams/create');
    return mapJsonList(data['users'], AppUser.fromJson);
  }

  Future<AppTeam> createTeam(Map<String, dynamic> body) async {
    final data = await _send('POST', '/admin/teams', body: body);
    return AppTeam.fromJson(_asMap(data['data'] ?? data));
  }

  Future<
      ({
        AppTeam team,
        List<AppUser> users,
        List<AppProject> projects,
        List<int> selectedUserIds,
        List<int> selectedProjectIds,
        int? selectedTeamLeadId,
      })> adminTeam(int id) async {
    final data = await _send('GET', '/admin/teams/$id/edit');
    return (
      team: AppTeam.fromJson(_asMap(data['team'])),
      users: mapJsonList(data['users'], AppUser.fromJson),
      projects: mapJsonList(data['projects'], AppProject.fromJson),
      selectedUserIds: _intList(data['selected_user_ids']),
      selectedProjectIds: _intList(data['selected_project_ids']),
      selectedTeamLeadId: data['selected_team_lead_id'] as int?,
    );
  }

  Future<AppTeam> updateTeam(int id, Map<String, dynamic> body) async {
    final data = await _send('PUT', '/admin/teams/$id', body: body);
    return AppTeam.fromJson(_asMap(data['team'] ?? data['data']));
  }

  Future<List<AppProject>> adminProjects() async {
    final data = await _send('GET', '/admin/projects');
    return mapJsonList(_laravelList(data), AppProject.fromJson);
  }

  Future<List<AppTeam>> adminProjectFormTeams() async {
    final data = await _send('GET', '/admin/projects/create');
    return mapJsonList(data['teams'], AppTeam.fromJson);
  }

  Future<AppProject> createProject(Map<String, dynamic> body) async {
    final data = await _send('POST', '/admin/projects', body: body);
    return AppProject.fromJson(_asMap(data['data'] ?? data));
  }

  Future<({AppProject project, List<AppTeam> teams, List<int> selectedTeamIds})> adminProject(int id) async {
    final data = await _send('GET', '/admin/projects/$id/edit');
    return (
      project: AppProject.fromJson(_asMap(data['project'])),
      teams: mapJsonList(data['teams'], AppTeam.fromJson),
      selectedTeamIds: _intList(data['selected_team_ids']),
    );
  }

  Future<AppProject> updateProject(int id, Map<String, dynamic> body) async {
    final data = await _send('PUT', '/admin/projects/$id', body: body);
    return AppProject.fromJson(_asMap(data['project'] ?? data['data']));
  }

  /// One HTTP call. Adds the token. Turns Laravel JSON into Dart maps or ApiException.
  Future<Map<String, dynamic>> _send(
    String method,
    String path, {
    Map<String, dynamic>? body,
    Map<String, dynamic>? query,
    bool allowEmpty = false,
  }) async {
    try {
      final response = await _dio.request<dynamic>(
        path,
        data: body,
        queryParameters: query,
        options: Options(method: method),
      );

      if (response.statusCode == 204 || response.data == null || response.data == '') {
        if (allowEmpty || response.statusCode == 204) {
          return {};
        }
      }

      if (response.statusCode != null && response.statusCode! >= 400) {
        throw _fromResponse(response);
      }

      if (response.data is Map<String, dynamic>) {
        return response.data as Map<String, dynamic>;
      }

      return {'data': response.data};
    } on DioException catch (error) {
      if (error.error is ApiException) {
        throw error.error as ApiException;
      }
      throw ApiException(error.message ?? 'Request failed');
    }
  }

  ApiException _fromResponse(Response<dynamic> response) {
    final data = response.data;
    if (data is Map<String, dynamic>) {
      final errors = <String, List<String>>{};
      final rawErrors = data['errors'];
      if (rawErrors is Map) {
        rawErrors.forEach((key, value) {
          if (value is List) {
            errors[key.toString()] = value.map((item) => item.toString()).toList();
          } else if (value != null) {
            errors[key.toString()] = [value.toString()];
          }
        });
      }

      return ApiException(
        data['message']?.toString() ?? 'Request failed',
        fieldErrors: errors,
      );
    }

    return ApiException('Request failed');
  }

  Map<String, dynamic> _asMap(dynamic value) {
    if (value is Map<String, dynamic>) {
      return value;
    }

    return {};
  }

  /// Laravel wraps lists as `{ "data": [ ... ] }`.
  List<dynamic> _laravelList(Map<String, dynamic> json) {
    if (json['data'] is List) {
      return json['data'] as List;
    }

    return const [];
  }

  List<int> _intList(dynamic value) {
    if (value is! List) {
      return const [];
    }

    return value.map((item) => item as int).toList();
  }
}
