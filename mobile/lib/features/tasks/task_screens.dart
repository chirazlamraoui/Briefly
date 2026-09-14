import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/api_exception.dart';
import '../../l10n/app_localizations.dart';
import '../../models/models.dart';
import '../../providers/providers.dart';
import '../../widgets/widgets.dart';

const taskStatuses = ['TODO', 'IN_PROGRESS', 'BLOCKED', 'DONE'];

String statusLabel(AppLocalizations l10n, String status) {
  return switch (status) {
    'IN_PROGRESS' => l10n.statusInProgress,
    'BLOCKED' => l10n.statusBlocked,
    'DONE' => l10n.statusDone,
    _ => l10n.statusTodo,
  };
}

class TaskListScreen extends ConsumerStatefulWidget {
  const TaskListScreen({super.key});

  @override
  ConsumerState<TaskListScreen> createState() => _TaskListScreenState();
}

class _TaskListScreenState extends ConsumerState<TaskListScreen> {
  late Future<List<AppTask>> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).tasks();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.myTasks),
        actions: [
          IconButton(onPressed: () => context.push('/tasks/history'), icon: const Icon(Icons.history)),
        ],
      ),
      body: FutureBuilder<List<AppTask>>(
        future: _future,
        builder: (context, snapshot) {
          return AsyncBody<List<AppTask>>(
            snapshot: snapshot,
            onRetry: () => setState(() => _future = ref.read(apiClientProvider).tasks()),
            builder: (tasks) {
              if (tasks.isEmpty) {
                return EmptyState(message: l10n.emptyTasks);
              }

              return ListView.separated(
                itemCount: tasks.length,
                separatorBuilder: (_, _) => const Divider(height: 1),
                itemBuilder: (context, index) {
                  final task = tasks[index];
                  return ListTile(
                    title: Text(task.title),
                    subtitle: Text([task.teamLabel, task.project?.name].whereType<String>().join(' · ')),
                    trailing: StatusPill(status: task.status),
                    onTap: () => context.push('/tasks/${task.id}'),
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

class TaskHistoryScreen extends ConsumerStatefulWidget {
  const TaskHistoryScreen({super.key});

  @override
  ConsumerState<TaskHistoryScreen> createState() => _TaskHistoryScreenState();
}

class _TaskHistoryScreenState extends ConsumerState<TaskHistoryScreen> {
  late Future<PaginatedUpdates> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).taskHistory();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.taskHistory)),
      body: FutureBuilder<PaginatedUpdates>(
        future: _future,
        builder: (context, snapshot) {
          return AsyncBody<PaginatedUpdates>(
            snapshot: snapshot,
            onRetry: () => setState(() => _future = ref.read(apiClientProvider).taskHistory()),
            builder: (page) {
              if (page.items.isEmpty) {
                return EmptyState(message: l10n.emptyHistory);
              }

              return ListView(
                children: [
                  for (final update in page.items)
                    ListTile(
                      title: Text(update.task?.title ?? ''),
                      subtitle: Text(update.progressDone ?? update.createdAt ?? ''),
                      trailing: StatusPill(status: update.status),
                    ),
                ],
              );
            },
          );
        },
      ),
    );
  }
}

class TaskDetailScreen extends ConsumerStatefulWidget {
  const TaskDetailScreen({super.key, required this.taskId});

  final int taskId;

  @override
  ConsumerState<TaskDetailScreen> createState() => _TaskDetailScreenState();
}

class _TaskDetailScreenState extends ConsumerState<TaskDetailScreen> {
  late Future<({AppTask task, bool canUpdateProgress, bool canEdit})> _future;

  @override
  void initState() {
    super.initState();
    _load();
  }

  void _load() {
    _future = ref.read(apiClientProvider).task(widget.taskId);
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder(
      future: _future,
      builder: (context, snapshot) {
        return AsyncBody(
          snapshot: snapshot,
          onRetry: () => setState(_load),
          builder: (data) {
            return _TaskDetailBody(
              data: data,
              onChanged: () => setState(_load),
            );
          },
        );
      },
    );
  }
}

class _TaskDetailBody extends ConsumerStatefulWidget {
  const _TaskDetailBody({required this.data, required this.onChanged});

  final ({AppTask task, bool canUpdateProgress, bool canEdit}) data;
  final VoidCallback onChanged;

  @override
  ConsumerState<_TaskDetailBody> createState() => _TaskDetailBodyState();
}

class _TaskDetailBodyState extends ConsumerState<_TaskDetailBody> {
  late String _status;
  late TextEditingController _done;
  late TextEditingController _next;
  late TextEditingController _blocker;
  String? _blockerError;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final task = widget.data.task;
    _status = task.status;
    _done = TextEditingController(text: task.progressDone ?? '');
    _next = TextEditingController(text: task.progressNext ?? '');
    _blocker = TextEditingController(text: task.blockerNote ?? '');
  }

  @override
  void dispose() {
    _done.dispose();
    _next.dispose();
    _blocker.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final l10n = AppLocalizations.of(context)!;
    final error = validateBlockerNote(_status, _blocker.text);
    if (error != null) {
      setState(() => _blockerError = l10n.blockerRequired);
      return;
    }

    setState(() {
      _saving = true;
      _blockerError = null;
    });

    try {
      await ref.read(apiClientProvider).updateProgress(
            widget.data.task.id,
            status: _status,
            progressDone: _done.text,
            progressNext: _next.text,
            blockerNote: _blocker.text,
          );
      widget.onChanged();
    } on ApiException catch (error) {
      setState(() => _blockerError = error.firstFieldError('blocker_note') ?? error.message);
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final task = widget.data.task;

    return Scaffold(
      appBar: AppBar(
        title: Text(task.title),
        actions: [
          if (widget.data.canEdit)
            IconButton(onPressed: () => context.push('/tasks/${task.id}/edit'), icon: const Icon(Icons.edit)),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (task.description != null && task.description!.isNotEmpty) Text(task.description!),
          const SizedBox(height: 8),
          Text(task.project?.name ?? ''),
          if (task.teamLabel != null) Text(task.teamLabel!),
          const SizedBox(height: 8),
          StatusPill(status: task.status),
          if (widget.data.canUpdateProgress) ...[
            const SizedBox(height: 24),
            Text(l10n.updateProgress, style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              initialValue: _status,
              items: [
                for (final status in taskStatuses)
                  DropdownMenuItem(value: status, child: Text(statusLabel(l10n, status))),
              ],
              onChanged: (value) => setState(() => _status = value ?? _status),
              decoration: InputDecoration(labelText: l10n.status),
            ),
            const SizedBox(height: 12),
            TextField(controller: _done, maxLines: 3, decoration: InputDecoration(labelText: l10n.progressDone)),
            const SizedBox(height: 12),
            TextField(controller: _next, maxLines: 3, decoration: InputDecoration(labelText: l10n.progressNext)),
            const SizedBox(height: 12),
            TextField(
              controller: _blocker,
              maxLines: 3,
              decoration: InputDecoration(labelText: l10n.blockerNote, errorText: _blockerError),
            ),
            const SizedBox(height: 16),
            FilledButton(onPressed: _saving ? null : _save, child: Text(l10n.saveProgress)),
          ],
          const SizedBox(height: 24),
          Text(l10n.history, style: Theme.of(context).textTheme.titleMedium),
          for (final update in task.updates)
            ListTile(
              contentPadding: EdgeInsets.zero,
              title: Text(update.user?.name ?? statusLabel(l10n, update.status)),
              subtitle: Text(update.progressDone ?? update.createdAt ?? ''),
              trailing: StatusPill(status: update.status),
            ),
        ],
      ),
    );
  }
}

class TaskFormScreen extends ConsumerStatefulWidget {
  const TaskFormScreen({super.key, this.projectId, this.taskId});

  final int? projectId;
  final int? taskId;

  @override
  ConsumerState<TaskFormScreen> createState() => _TaskFormScreenState();
}

class _TaskFormScreenState extends ConsumerState<TaskFormScreen> {
  final _title = TextEditingController();
  final _description = TextEditingController();
  String _status = 'TODO';
  int? _assigneeId;
  List<AppUser> _members = [];
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
      if (widget.taskId != null) {
        final data = await api.taskEditForm(widget.taskId!);
        _title.text = data.task.title;
        _description.text = data.task.description ?? '';
        _status = data.task.status;
        _assigneeId = data.task.assignee?.id;
        _members = data.members;
      } else if (widget.projectId != null) {
        final data = await api.taskCreateForm(widget.projectId!);
        _members = data.members;
        if (_members.isNotEmpty) {
          _assigneeId = _members.first.id;
        }
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
    _title.dispose();
    _description.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final l10n = AppLocalizations.of(context)!;
    if (_title.text.trim().isEmpty || _assigneeId == null) {
      setState(() => _error = l10n.requiredField);
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      if (widget.taskId != null) {
        await api.updateTask(
          widget.taskId!,
          title: _title.text.trim(),
          description: _description.text.trim(),
          assignedTo: _assigneeId!,
          status: _status,
        );
      } else {
        await api.createTask(
          widget.projectId!,
          title: _title.text.trim(),
          description: _description.text.trim(),
          assignedTo: _assigneeId!,
          status: _status,
        );
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
      appBar: AppBar(title: Text(widget.taskId == null ? l10n.createTask : l10n.editTask)),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                TextField(controller: _title, decoration: InputDecoration(labelText: l10n.title)),
                const SizedBox(height: 12),
                TextField(controller: _description, maxLines: 4, decoration: InputDecoration(labelText: l10n.description)),
                const SizedBox(height: 12),
                DropdownButtonFormField<int>(
                  initialValue: _assigneeId,
                  items: [
                    for (final member in _members) DropdownMenuItem(value: member.id, child: Text(member.name)),
                  ],
                  onChanged: (value) => setState(() => _assigneeId = value),
                  decoration: InputDecoration(labelText: l10n.assignee),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  initialValue: _status,
                  items: [
                    for (final status in taskStatuses)
                      DropdownMenuItem(value: status, child: Text(statusLabel(l10n, status))),
                  ],
                  onChanged: (value) => setState(() => _status = value ?? _status),
                  decoration: InputDecoration(labelText: l10n.status),
                ),
                if (_error != null) ...[
                  const SizedBox(height: 12),
                  Text(_error!, style: const TextStyle(color: Colors.red)),
                ],
                const SizedBox(height: 16),
                FilledButton(onPressed: _saving ? null : _save, child: Text(widget.taskId == null ? l10n.create : l10n.save)),
              ],
            ),
    );
  }
}
