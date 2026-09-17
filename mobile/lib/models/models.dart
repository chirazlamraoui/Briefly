/// Copies of the JSON Laravel sends (UserResource, TaskResource, ...).
class AppUser {
  const AppUser({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    this.jobTitle,
    this.isTeamLead = false,
  });

  final int id;
  final String name;
  final String email;
  final String role;
  final String? jobTitle;
  final bool isTeamLead;

  bool get isAdmin => role == 'ADMIN';
  bool get isLead => isTeamLead || role == 'TEAM_LEAD';

  factory AppUser.fromJson(Map<String, dynamic> json) {
    return AppUser(
      id: json['id'] as int,
      name: json['name'] as String,
      email: json['email'] as String? ?? '',
      role: json['role'] as String,
      jobTitle: json['job_title'] as String?,
      isTeamLead: json['is_team_lead'] == true,
    );
  }
}

class AppTeam {
  const AppTeam({
    required this.id,
    required this.name,
    this.teamLead,
  });

  final int id;
  final String name;
  final AppUser? teamLead;

  factory AppTeam.fromJson(Map<String, dynamic> json) {
    return AppTeam(
      id: json['id'] as int,
      name: json['name'] as String,
      teamLead: json['team_lead'] is Map<String, dynamic>
          ? AppUser.fromJson(json['team_lead'] as Map<String, dynamic>)
          : null,
    );
  }
}

class AppProject {
  const AppProject({
    required this.id,
    required this.name,
    this.description,
    this.tasksCount,
    this.teamsSummary,
    this.teams = const [],
  });

  final int id;
  final String name;
  final String? description;
  final int? tasksCount;
  final String? teamsSummary;
  final List<AppTeam> teams;

  factory AppProject.fromJson(Map<String, dynamic> json) {
    return AppProject(
      id: json['id'] as int,
      name: json['name'] as String,
      description: json['description'] as String?,
      tasksCount: json['tasks_count'] as int?,
      teamsSummary: json['teams_summary'] as String?,
      teams: mapJsonList(json['teams'], AppTeam.fromJson),
    );
  }
}

class AppTask {
  const AppTask({
    required this.id,
    required this.title,
    required this.status,
    this.description,
    this.progressDone,
    this.progressNext,
    this.blockerNote,
    this.project,
    this.assignee,
    this.updates = const [],
    this.teamLabel,
  });

  final int id;
  final String title;
  final String status;
  final String? description;
  final String? progressDone;
  final String? progressNext;
  final String? blockerNote;
  final AppProject? project;
  final AppUser? assignee;
  final List<AppTaskUpdate> updates;
  final String? teamLabel;

  factory AppTask.fromJson(Map<String, dynamic> json) {
    return AppTask(
      id: json['id'] as int,
      title: json['title'] as String,
      status: json['status'] as String,
      description: json['description'] as String?,
      progressDone: json['progress_done'] as String?,
      progressNext: json['progress_next'] as String?,
      blockerNote: json['blocker_note'] as String?,
      project: json['project'] is Map<String, dynamic>
          ? AppProject.fromJson(json['project'] as Map<String, dynamic>)
          : null,
      assignee: json['assignee'] is Map<String, dynamic>
          ? AppUser.fromJson(json['assignee'] as Map<String, dynamic>)
          : null,
      updates: mapJsonList(json['updates'], AppTaskUpdate.fromJson),
      teamLabel: json['team_label'] as String?,
    );
  }
}

class AppTaskUpdate {
  const AppTaskUpdate({
    required this.id,
    required this.status,
    this.progressDone,
    this.createdAt,
    this.task,
    this.user,
  });

  final int id;
  final String status;
  final String? progressDone;
  final String? createdAt;
  final AppTask? task;
  final AppUser? user;

  factory AppTaskUpdate.fromJson(Map<String, dynamic> json) {
    return AppTaskUpdate(
      id: json['id'] as int,
      status: json['status'] as String,
      progressDone: json['progress_done'] as String?,
      createdAt: json['created_at'] as String?,
      task: json['task'] is Map<String, dynamic> ? AppTask.fromJson(json['task'] as Map<String, dynamic>) : null,
      user: json['user'] is Map<String, dynamic> ? AppUser.fromJson(json['user'] as Map<String, dynamic>) : null,
    );
  }
}

class RateRow {
  const RateRow({required this.name, required this.rate, this.id});

  final int? id;
  final String name;
  final int rate;

  factory RateRow.fromJson(Map<String, dynamic> json) {
    return RateRow(
      id: json['id'] as int?,
      name: json['name'] as String,
      rate: json['rate'] as int? ?? 0,
    );
  }
}

class DashboardData {
  const DashboardData({
    required this.overallRate,
    required this.doneCount,
    required this.totalCount,
    required this.tasks,
    required this.ledTeams,
  });

  final int overallRate;
  final int doneCount;
  final int totalCount;
  final List<AppTask> tasks;
  final List<LedTeamSection> ledTeams;
}

class LedTeamSection {
  const LedTeamSection({
    required this.team,
    required this.stats,
    required this.memberRates,
    required this.projectRates,
    required this.blockedTasks,
  });

  final AppTeam team;
  final Map<String, int> stats;
  final List<RateRow> memberRates;
  final List<RateRow> projectRates;
  final List<AppTask> blockedTasks;
}

class AdminDashboardData {
  const AdminDashboardData({
    required this.completionRate,
    required this.statusLabels,
    required this.statusValues,
    required this.statusColors,
    required this.teamRates,
    required this.projectRates,
  });

  final int completionRate;
  final List<String> statusLabels;
  final List<int> statusValues;
  final List<String> statusColors;
  final List<RateRow> teamRates;
  final List<RateRow> projectRates;
}

class PaginatedUpdates {
  const PaginatedUpdates({required this.items});

  final List<AppTaskUpdate> items;
}

List<T> mapJsonList<T>(dynamic value, T Function(Map<String, dynamic> json) map) {
  if (value is! List) {
    return const [];
  }

  return value.whereType<Map<String, dynamic>>().map(map).toList();
}

String? validateBlockerNote(String status, String blockerNote) {
  if (status == 'BLOCKED' && blockerNote.trim().isEmpty) {
    return 'blocker';
  }

  return null;
}
