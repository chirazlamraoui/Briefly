import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../l10n/app_localizations.dart';
import '../models/models.dart';
import '../providers/providers.dart';
import '../theme/briefly_theme.dart';

class StatusPill extends StatelessWidget {
  const StatusPill({super.key, required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final colors = switch (status) {
      'DONE' => (const Color(0xFFDCFCE7), const Color(0xFF166534), l10n.statusDone),
      'IN_PROGRESS' => (const Color(0xFFDBEAFE), const Color(0xFF1D4ED8), l10n.statusInProgress),
      'BLOCKED' => (const Color(0xFFFEE2E2), const Color(0xFFB91C1C), l10n.statusBlocked),
      _ => (const Color(0xFFF4F4F5), const Color(0xFF3F3F46), l10n.statusTodo),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: colors.$1, borderRadius: BorderRadius.circular(999)),
      child: Text(colors.$3, style: TextStyle(color: colors.$2, fontSize: 12, fontWeight: FontWeight.w600)),
    );
  }
}

class CompletionRing extends StatelessWidget {
  const CompletionRing({super.key, required this.rate, this.size = 88});

  final int rate;
  final double size;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: size,
      height: size,
      child: Stack(
        alignment: Alignment.center,
        children: [
          CircularProgressIndicator(
            value: rate / 100,
            strokeWidth: 8,
            color: BrieflyColors.accent,
            backgroundColor: Theme.of(context).dividerColor.withValues(alpha: 0.3),
          ),
          Text('$rate%', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
        ],
      ),
    );
  }
}

class EmptyState extends StatelessWidget {
  const EmptyState({super.key, required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Text(message, textAlign: TextAlign.center, style: Theme.of(context).textTheme.bodyLarge),
      ),
    );
  }
}

class ErrorView extends StatelessWidget {
  const ErrorView({super.key, required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            FilledButton(onPressed: onRetry, child: Text(l10n.retry)),
          ],
        ),
      ),
    );
  }
}

class AsyncBody<T> extends StatelessWidget {
  const AsyncBody({
    super.key,
    required this.snapshot,
    required this.builder,
    this.onRetry,
  });

  final AsyncSnapshot<T> snapshot;
  final Widget Function(T data) builder;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    if (snapshot.connectionState == ConnectionState.waiting && !snapshot.hasData) {
      return const Center(child: CircularProgressIndicator());
    }

    if (snapshot.hasError) {
      return ErrorView(message: snapshot.error.toString(), onRetry: onRetry ?? () {});
    }

    if (!snapshot.hasData) {
      return const Center(child: CircularProgressIndicator());
    }

    return builder(snapshot.data as T);
  }
}

class AppShell extends ConsumerWidget {
  const AppShell({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final user = ref.watch(authProvider).user;
    final location = GoRouterState.of(context).uri.path;
    final destinations = _destinations(l10n, user);

    final selected = destinations.indexWhere((item) => location == item.path || location.startsWith('${item.path}/'));
    final index = selected < 0 ? 0 : selected;

    return Scaffold(
      body: child,
      bottomNavigationBar: BrieflyNavBar(
        destinations: destinations,
        selectedIndex: index,
        onSelected: (value) => context.go(destinations[value].path),
      ),
    );
  }

  List<({String path, String label, IconData icon})> _destinations(AppLocalizations l10n, AppUser? user) {
    if (user?.isAdmin == true) {
      return [
        (path: '/admin', label: l10n.navOverview, icon: Icons.pie_chart_outline),
        (path: '/admin/projects', label: l10n.navProjects, icon: Icons.folder_outlined),
        (path: '/admin/teams', label: l10n.navTeams, icon: Icons.groups_outlined),
        (path: '/admin/users', label: l10n.navUsers, icon: Icons.people_outline),
        (path: '/profile', label: l10n.navProfile, icon: Icons.person_outline),
      ];
    }

    if (user?.isLead == true) {
      return [
        (path: '/dashboard', label: l10n.navDashboard, icon: Icons.home_outlined),
        (path: '/tasks', label: l10n.navTasks, icon: Icons.checklist_outlined),
        (path: '/team/tasks', label: l10n.navTeamTasks, icon: Icons.groups_outlined),
        (path: '/projects', label: l10n.navProjects, icon: Icons.folder_outlined),
        (path: '/profile', label: l10n.navProfile, icon: Icons.person_outline),
      ];
    }

    return [
      (path: '/dashboard', label: l10n.navDashboard, icon: Icons.home_outlined),
      (path: '/tasks', label: l10n.navTasks, icon: Icons.checklist_outlined),
      (path: '/profile', label: l10n.navProfile, icon: Icons.person_outline),
    ];
  }
}

class BrieflyNavBar extends StatelessWidget {
  const BrieflyNavBar({
    super.key,
    required this.destinations,
    required this.selectedIndex,
    required this.onSelected,
  });

  final List<({String path, String label, IconData icon})> destinations;
  final int selectedIndex;
  final ValueChanged<int> onSelected;

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Material(
      color: colorScheme.surface,
      elevation: 0,
      child: DecoratedBox(
        decoration: BoxDecoration(
          border: Border(top: BorderSide(color: Theme.of(context).dividerColor.withValues(alpha: 0.4))),
        ),
        child: SafeArea(
          top: false,
          child: SizedBox(
            height: 64,
            child: Row(
              children: [
                for (var i = 0; i < destinations.length; i++)
                  Expanded(
                    child: _NavItem(
                      icon: destinations[i].icon,
                      label: destinations[i].label,
                      selected: i == selectedIndex,
                      onTap: () => onSelected(i),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _NavItem extends StatelessWidget {
  const _NavItem({
    required this.icon,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final color = selected ? Theme.of(context).colorScheme.onSurface : BrieflyColors.textMuted;
    final selectedBg = isDark ? BrieflyColors.accent.withValues(alpha: 0.22) : BrieflyColors.accentSoft;

    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 4),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            AnimatedContainer(
              duration: const Duration(milliseconds: 180),
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
              decoration: BoxDecoration(
                color: selected ? selectedBg : Colors.transparent,
                borderRadius: BorderRadius.circular(20),
              ),
              child: Icon(icon, size: 22, color: color),
            ),
            const SizedBox(height: 4),
            FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(
                label,
                maxLines: 1,
                softWrap: false,
                style: Theme.of(context).textTheme.labelSmall?.copyWith(
                      fontSize: 11,
                      fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
                      color: color,
                      height: 1,
                    ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class MultiSelectChips extends StatelessWidget {
  const MultiSelectChips({
    super.key,
    required this.items,
    required this.selectedIds,
    required this.onChanged,
  });

  final List<({int id, String label})> items;
  final Set<int> selectedIds;
  final ValueChanged<Set<int>> onChanged;

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: [
        for (final item in items)
          FilterChip(
            label: Text(item.label),
            selected: selectedIds.contains(item.id),
            onSelected: (selected) {
              final next = {...selectedIds};
              if (selected) {
                next.add(item.id);
              } else {
                next.remove(item.id);
              }
              onChanged(next);
            },
          ),
      ],
    );
  }
}
