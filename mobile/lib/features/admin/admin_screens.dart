import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/api_exception.dart';
import '../../l10n/app_localizations.dart';
import '../../models/models.dart';
import '../../providers/providers.dart';
import '../../theme/briefly_theme.dart';
import '../../widgets/widgets.dart';

class AdminDashboardScreen extends ConsumerStatefulWidget {
  const AdminDashboardScreen({super.key});

  @override
  ConsumerState<AdminDashboardScreen> createState() => _AdminDashboardScreenState();
}

class _AdminDashboardScreenState extends ConsumerState<AdminDashboardScreen> {
  late Future<AdminDashboardData> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).adminDashboard();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.overview)),
      body: FutureBuilder<AdminDashboardData>(
        future: _future,
        builder: (context, snapshot) {
          return AsyncBody<AdminDashboardData>(
            snapshot: snapshot,
            onRetry: () => setState(() => _future = ref.read(apiClientProvider).adminDashboard()),
            builder: (data) {
              return ListView(
                padding: BrieflySpacing.page,
                children: [
                  CompletionSummaryCard(
                    rate: data.completionRate,
                    label: l10n.completionRate,
                  ),
                  const SizedBox(height: 12),
                  BrieflyCard(
                    child: SizedBox(
                      height: 220,
                      child: PieChart(
                        PieChartData(
                          sectionsSpace: 2,
                          centerSpaceRadius: 36,
                          sections: [
                            for (var i = 0; i < data.statusValues.length; i++)
                              PieChartSectionData(
                                value: data.statusValues[i].toDouble(),
                                color: _parseColor(data.statusColors, i),
                                title: '${data.statusLabels[i]}\n${data.statusValues[i]}',
                                radius: 64,
                                titleStyle: Theme.of(context).textTheme.labelSmall?.copyWith(
                                      fontWeight: FontWeight.w700,
                                      color: BrieflyColors.surface,
                                    ),
                              ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  SectionHeader(title: l10n.teams),
                  GroupedCard(
                    children: [
                      for (final row in data.teamRates)
                        RateListTile(name: row.name, rate: row.rate, grouped: true),
                    ],
                  ),
                  SectionHeader(title: l10n.projects),
                  GroupedCard(
                    children: [
                      for (final row in data.projectRates)
                        RateListTile(name: row.name, rate: row.rate, grouped: true),
                    ],
                  ),
                ],
              );
            },
          );
        },
      ),
    );
  }

  Color _parseColor(List<String> colors, int index) {
    if (index >= colors.length) {
      return Theme.of(context).colorScheme.outline;
    }
    final hex = colors[index].replaceFirst('#', '');
    return Color(int.parse('FF$hex', radix: 16));
  }
}

class AdminUsersScreen extends ConsumerStatefulWidget {
  const AdminUsersScreen({super.key});

  @override
  ConsumerState<AdminUsersScreen> createState() => _AdminUsersScreenState();
}

class _AdminUsersScreenState extends ConsumerState<AdminUsersScreen> {
  late Future<List<AppUser>> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).adminUsers();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.users)),
      floatingActionButton: FloatingActionButton(
        onPressed: () => context.push('/admin/users/create'),
        child: const Icon(Icons.add),
      ),
      body: FutureBuilder<List<AppUser>>(
        future: _future,
        builder: (context, snapshot) {
          return AsyncBody<List<AppUser>>(
            snapshot: snapshot,
            onRetry: () => setState(() => _future = ref.read(apiClientProvider).adminUsers()),
            builder: (users) {
              if (users.isEmpty) {
                return EmptyState(message: l10n.emptyUsers, icon: Icons.people_outline);
              }

              return ListView.builder(
                padding: BrieflySpacing.pageWithFab,
                itemCount: users.length,
                itemBuilder: (context, index) {
                  final user = users[index];
                  return EntityListTile(
                    title: user.name,
                    subtitle: user.email,
                    leading: InitialAvatar(name: user.name),
                    onTap: () => context.push('/admin/users/${user.id}'),
                  );
                },
              );
            },
          );
        },
      ),
    );
  }
}

class AdminUserFormScreen extends ConsumerStatefulWidget {
  const AdminUserFormScreen({super.key, this.userId});

  final int? userId;

  @override
  ConsumerState<AdminUserFormScreen> createState() => _AdminUserFormScreenState();
}

class _AdminUserFormScreenState extends ConsumerState<AdminUserFormScreen> {
  final _name = TextEditingController();
  final _job = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  List<AppTeam> _teams = [];
  Set<int> _teamIds = {};
  Set<int> _leadIds = {};
  bool _loading = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    final api = ref.read(apiClientProvider);
    try {
      if (widget.userId == null) {
        _teams = await api.adminUserFormTeams();
      } else {
        final data = await api.adminUser(widget.userId!);
        _name.text = data.user.name;
        _job.text = data.user.jobTitle ?? '';
        _email.text = data.user.email;
        _teams = data.teams;
        _teamIds = data.selectedTeamIds.toSet();
        _leadIds = data.selectedTeamLeadIds.toSet();
      }
    } on ApiException catch (error) {
      _error = error.message;
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  void dispose() {
    _name.dispose();
    _job.dispose();
    _email.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final l10n = AppLocalizations.of(context)!;
    if (_name.text.trim().isEmpty || _teamIds.isEmpty) {
      setState(() => _error = l10n.requiredField);
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final body = {
        'name': _name.text.trim(),
        'job_title': _job.text.trim(),
        'team_ids': _teamIds.toList(),
        'team_lead_ids': _leadIds.toList(),
      };

      if (widget.userId == null) {
        await api.createUser({
          ...body,
          'email': _email.text.trim(),
          'password': _password.text,
          'password_confirmation': _confirm.text,
        });
      } else {
        await api.updateUser(widget.userId!, body);
      }

      if (mounted) {
        context.pop();
      }
    } on ApiException catch (error) {
      setState(() => _error = error.message);
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(title: Text(widget.userId == null ? l10n.newUser : l10n.editUser)),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: BrieflySpacing.page,
              children: [
                FormSection(
                  children: [
                    TextField(controller: _name, decoration: InputDecoration(labelText: l10n.name)),
                    TextField(controller: _job, decoration: InputDecoration(labelText: l10n.jobTitle)),
                    if (widget.userId == null) ...[
                      TextField(controller: _email, decoration: InputDecoration(labelText: l10n.email)),
                      TextField(
                        controller: _password,
                        obscureText: true,
                        decoration: InputDecoration(labelText: l10n.password),
                      ),
                      TextField(
                        controller: _confirm,
                        obscureText: true,
                        decoration: InputDecoration(labelText: l10n.confirmPassword),
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 8),
                FormSection(
                  title: l10n.teams,
                  children: [
                    MultiSelectChips(
                      items: [for (final team in _teams) (id: team.id, label: team.name)],
                      selectedIds: _teamIds,
                      onChanged: (value) => setState(() {
                        _teamIds = value;
                        _leadIds = _leadIds.intersection(value);
                      }),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                FormSection(
                  title: l10n.teamLead,
                  children: [
                    MultiSelectChips(
                      items: [
                        for (final team in _teams.where((team) => _teamIds.contains(team.id)))
                          (id: team.id, label: team.name),
                      ],
                      selectedIds: _leadIds,
                      onChanged: (value) => setState(() => _leadIds = value),
                    ),
                  ],
                ),
                if (_error != null) ...[
                  const SizedBox(height: 12),
                  FormBanner.error(_error!),
                ],
                const SizedBox(height: 16),
                LoadingFilledButton(onPressed: _save, label: l10n.save, loading: _saving),
              ],
            ),
    );
  }
}

class AdminTeamsScreen extends ConsumerStatefulWidget {
  const AdminTeamsScreen({super.key});

  @override
  ConsumerState<AdminTeamsScreen> createState() => _AdminTeamsScreenState();
}

class _AdminTeamsScreenState extends ConsumerState<AdminTeamsScreen> {
  late Future<List<AppTeam>> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).adminTeams();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.teams)),
      floatingActionButton: FloatingActionButton(
        onPressed: () => context.push('/admin/teams/create'),
        child: const Icon(Icons.add),
      ),
      body: FutureBuilder<List<AppTeam>>(
        future: _future,
        builder: (context, snapshot) {
          return AsyncBody<List<AppTeam>>(
            snapshot: snapshot,
            onRetry: () => setState(() => _future = ref.read(apiClientProvider).adminTeams()),
            builder: (teams) {
              if (teams.isEmpty) {
                return EmptyState(message: l10n.emptyTeams, icon: Icons.groups_outlined);
              }

              return ListView.builder(
                padding: BrieflySpacing.pageWithFab,
                itemCount: teams.length,
                itemBuilder: (context, index) {
                  final team = teams[index];
                  return EntityListTile(
                    title: team.name,
                    subtitle: team.teamLead?.name ?? l10n.none,
                    leading: const Icon(Icons.groups_outlined),
                    onTap: () => context.push('/admin/teams/${team.id}/edit'),
                  );
                },
              );
            },
          );
        },
      ),
    );
  }
}

class AdminTeamFormScreen extends ConsumerStatefulWidget {
  const AdminTeamFormScreen({super.key, this.teamId});

  final int? teamId;

  @override
  ConsumerState<AdminTeamFormScreen> createState() => _AdminTeamFormScreenState();
}

class _AdminTeamFormScreenState extends ConsumerState<AdminTeamFormScreen> {
  final _name = TextEditingController();
  List<AppUser> _users = [];
  List<AppProject> _projects = [];
  Set<int> _userIds = {};
  Set<int> _projectIds = {};
  int? _leadId;
  bool _loading = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    final api = ref.read(apiClientProvider);
    try {
      if (widget.teamId == null) {
        _users = await api.adminTeamFormUsers();
      } else {
        final data = await api.adminTeam(widget.teamId!);
        _name.text = data.team.name;
        _users = data.users;
        _projects = data.projects;
        _userIds = data.selectedUserIds.toSet();
        _projectIds = data.selectedProjectIds.toSet();
        _leadId = data.selectedTeamLeadId;
      }
    } on ApiException catch (error) {
      _error = error.message;
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  void dispose() {
    _name.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final l10n = AppLocalizations.of(context)!;
    if (_name.text.trim().isEmpty) {
      setState(() => _error = l10n.requiredField);
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final body = {
        'name': _name.text.trim(),
        'user_ids': _userIds.toList(),
        'team_lead_id': _leadId,
        if (widget.teamId != null) 'project_ids': _projectIds.toList(),
      };

      if (widget.teamId == null) {
        await api.createTeam(body);
      } else {
        await api.updateTeam(widget.teamId!, body);
      }

      if (mounted) {
        context.pop();
      }
    } on ApiException catch (error) {
      setState(() => _error = error.message);
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(title: Text(widget.teamId == null ? l10n.newTeam : l10n.editTeam)),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: BrieflySpacing.page,
              children: [
                FormSection(
                  children: [
                    TextField(controller: _name, decoration: InputDecoration(labelText: l10n.teamName)),
                  ],
                ),
                const SizedBox(height: 8),
                FormSection(
                  title: l10n.members,
                  children: [
                    MultiSelectChips(
                      items: [for (final user in _users) (id: user.id, label: user.name)],
                      selectedIds: _userIds,
                      onChanged: (value) => setState(() => _userIds = value),
                    ),
                    DropdownButtonFormField<int?>(
                      initialValue: _leadId,
                      items: [
                        DropdownMenuItem<int?>(value: null, child: Text(l10n.none)),
                        for (final user in _users) DropdownMenuItem<int?>(value: user.id, child: Text(user.name)),
                      ],
                      onChanged: (value) => setState(() => _leadId = value),
                      decoration: InputDecoration(labelText: l10n.teamLead),
                    ),
                  ],
                ),
                if (widget.teamId != null) ...[
                  const SizedBox(height: 8),
                  FormSection(
                    title: l10n.projects,
                    children: [
                      MultiSelectChips(
                        items: [for (final project in _projects) (id: project.id, label: project.name)],
                        selectedIds: _projectIds,
                        onChanged: (value) => setState(() => _projectIds = value),
                      ),
                    ],
                  ),
                ],
                if (_error != null) ...[
                  const SizedBox(height: 12),
                  FormBanner.error(_error!),
                ],
                const SizedBox(height: 16),
                LoadingFilledButton(onPressed: _save, label: l10n.save, loading: _saving),
              ],
            ),
    );
  }
}

class AdminProjectsScreen extends ConsumerStatefulWidget {
  const AdminProjectsScreen({super.key});

  @override
  ConsumerState<AdminProjectsScreen> createState() => _AdminProjectsScreenState();
}

class _AdminProjectsScreenState extends ConsumerState<AdminProjectsScreen> {
  late Future<List<AppProject>> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).adminProjects();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.projects)),
      floatingActionButton: FloatingActionButton(
        onPressed: () => context.push('/admin/projects/create'),
        child: const Icon(Icons.add),
      ),
      body: FutureBuilder<List<AppProject>>(
        future: _future,
        builder: (context, snapshot) {
          return AsyncBody<List<AppProject>>(
            snapshot: snapshot,
            onRetry: () => setState(() => _future = ref.read(apiClientProvider).adminProjects()),
            builder: (projects) {
              if (projects.isEmpty) {
                return EmptyState(message: l10n.emptyProjects, icon: Icons.folder_outlined);
              }

              return ListView.builder(
                padding: BrieflySpacing.pageWithFab,
                itemCount: projects.length,
                itemBuilder: (context, index) {
                  final project = projects[index];
                  return EntityListTile(
                    title: project.name,
                    subtitle: project.teamsSummary,
                    leading: const Icon(Icons.folder_outlined),
                    onTap: () => context.push('/admin/projects/${project.id}/edit'),
                  );
                },
              );
            },
          );
        },
      ),
    );
  }
}

class AdminProjectFormScreen extends ConsumerStatefulWidget {
  const AdminProjectFormScreen({super.key, this.projectId});

  final int? projectId;

  @override
  ConsumerState<AdminProjectFormScreen> createState() => _AdminProjectFormScreenState();
}

class _AdminProjectFormScreenState extends ConsumerState<AdminProjectFormScreen> {
  final _name = TextEditingController();
  final _description = TextEditingController();
  List<AppTeam> _teams = [];
  Set<int> _teamIds = {};
  bool _loading = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    final api = ref.read(apiClientProvider);
    try {
      if (widget.projectId == null) {
        _teams = await api.adminProjectFormTeams();
      } else {
        final data = await api.adminProject(widget.projectId!);
        _name.text = data.project.name;
        _description.text = data.project.description ?? '';
        _teams = data.teams;
        _teamIds = data.selectedTeamIds.toSet();
      }
    } on ApiException catch (error) {
      _error = error.message;
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  void dispose() {
    _name.dispose();
    _description.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final l10n = AppLocalizations.of(context)!;
    if (_name.text.trim().isEmpty) {
      setState(() => _error = l10n.requiredField);
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final body = {
        'name': _name.text.trim(),
        'description': _description.text.trim(),
        'team_ids': _teamIds.toList(),
      };

      if (widget.projectId == null) {
        await api.createProject(body);
      } else {
        await api.updateProject(widget.projectId!, body);
      }

      if (mounted) {
        context.pop();
      }
    } on ApiException catch (error) {
      setState(() => _error = error.message);
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(title: Text(widget.projectId == null ? l10n.newProject : l10n.editProject)),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: BrieflySpacing.page,
              children: [
                FormSection(
                  children: [
                    TextField(controller: _name, decoration: InputDecoration(labelText: l10n.projectName)),
                    TextField(
                      controller: _description,
                      maxLines: 4,
                      decoration: InputDecoration(labelText: l10n.description),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                FormSection(
                  title: l10n.teams,
                  children: [
                    MultiSelectChips(
                      items: [for (final team in _teams) (id: team.id, label: team.name)],
                      selectedIds: _teamIds,
                      onChanged: (value) => setState(() => _teamIds = value),
                    ),
                  ],
                ),
                if (_error != null) ...[
                  const SizedBox(height: 12),
                  FormBanner.error(_error!),
                ],
                const SizedBox(height: 16),
                LoadingFilledButton(onPressed: _save, label: l10n.save, loading: _saving),
              ],
            ),
    );
  }
}
