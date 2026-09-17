import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../l10n/app_localizations.dart';
import '../models/models.dart';
import '../providers/providers.dart';
import '../theme/briefly_theme.dart';

/// Shared screen pieces: status, cards, list rows, bottom bar.
const taskStatuses = ['TODO', 'IN_PROGRESS', 'BLOCKED', 'DONE'];

String statusLabel(AppLocalizations l10n, String status) {
  return switch (status) {
    'IN_PROGRESS' => l10n.statusInProgress,
    'BLOCKED' => l10n.statusBlocked,
    'DONE' => l10n.statusDone,
    _ => l10n.statusTodo,
  };
}

String? passwordPairError(String password, String confirm, AppLocalizations l10n) {
  if (password.length < 8) {
    return l10n.passwordMinLength;
  }
  if (password != confirm) {
    return l10n.passwordsDoNotMatch;
  }
  return null;
}

/// Coloured badge: TODO, IN_PROGRESS, BLOCKED, or DONE.
class StatusPill extends StatelessWidget {
  const StatusPill({super.key, required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final (background, foreground) = switch (status) {
      'DONE' => (
          isDark ? const Color(0xFF14532D) : const Color(0xFFDCFCE7),
          isDark ? const Color(0xFF86EFAC) : const Color(0xFF166534),
        ),
      'IN_PROGRESS' => (
          isDark ? const Color(0xFF1E3A8A) : const Color(0xFFDBEAFE),
          isDark ? const Color(0xFF93C5FD) : const Color(0xFF1D4ED8),
        ),
      'BLOCKED' => (
          isDark ? const Color(0xFF7F1D1D) : const Color(0xFFFEE2E2),
          isDark ? const Color(0xFFFCA5A5) : const Color(0xFFB91C1C),
        ),
      _ => (
          isDark ? const Color(0xFF27272A) : const Color(0xFFF4F4F5),
          isDark ? const Color(0xFFA1A1AA) : const Color(0xFF3F3F46),
        ),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: background, borderRadius: BorderRadius.circular(BrieflyRadii.pill)),
      child: Text(
        statusLabel(l10n, status),
        style: Theme.of(context).textTheme.labelSmall?.copyWith(
              color: foreground,
              fontWeight: FontWeight.w700,
            ),
      ),
    );
  }
}

class CompletionRing extends StatelessWidget {
  const CompletionRing({super.key, required this.rate, this.size = 88});

  final int rate;
  final double size;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

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
            backgroundColor: scheme.surfaceContainerHighest,
          ),
          Text('$rate%', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
        ],
      ),
    );
  }
}

class CompletionSummaryCard extends StatelessWidget {
  const CompletionSummaryCard({super.key, required this.rate, required this.label, this.subtitle});

  final int rate;
  final String label;
  final String? subtitle;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return BrieflyCard(
      child: Row(
        children: [
          CompletionRing(rate: rate),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                ),
                if (subtitle != null && subtitle!.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    subtitle!,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class EmptyState extends StatelessWidget {
  const EmptyState({
    super.key,
    required this.message,
    this.icon = Icons.inbox_outlined,
    this.compact = false,
  });

  final String message;
  final IconData icon;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Center(
      child: Padding(
        padding: EdgeInsets.all(compact ? 16 : 32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: compact ? 28 : 40, color: scheme.onSurfaceVariant),
            SizedBox(height: compact ? 8 : 12),
            Text(
              message,
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodyLarge?.copyWith(color: scheme.onSurfaceVariant),
            ),
          ],
        ),
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
    final scheme = Theme.of(context).colorScheme;

    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.error_outline, size: 40, color: scheme.error),
            const SizedBox(height: 12),
            Text(
              l10n.somethingWentWrong,
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            Text(
              message,
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
            ),
            const SizedBox(height: 16),
            FilledButton(
              style: FilledButton.styleFrom(minimumSize: const Size(160, 48)),
              onPressed: onRetry,
              child: Text(l10n.retry),
            ),
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

class SectionHeader extends StatelessWidget {
  const SectionHeader({super.key, required this.title});

  final String title;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(left: 4, right: 4, top: 8, bottom: 8),
      child: Text(
        title,
        style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
      ),
    );
  }
}

class BrieflyCard extends StatelessWidget {
  const BrieflyCard({super.key, required this.child, this.padding = BrieflySpacing.card});

  final Widget child;
  final EdgeInsetsGeometry padding;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(padding: padding, child: child),
    );
  }
}

class GroupedCard extends StatelessWidget {
  const GroupedCard({super.key, required this.children});

  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    if (children.isEmpty) {
      return const SizedBox.shrink();
    }

    return Card(
      child: Column(
        children: [
          for (var i = 0; i < children.length; i++) ...[
            if (i > 0) const Divider(height: 1),
            children[i],
          ],
        ],
      ),
    );
  }
}

class FormSection extends StatelessWidget {
  const FormSection({super.key, this.title, required this.children});

  final String? title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (title != null) SectionHeader(title: title!),
        BrieflyCard(
          padding: BrieflySpacing.form,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              for (var i = 0; i < children.length; i++) ...[
                if (i > 0) const SizedBox(height: 12),
                children[i],
              ],
            ],
          ),
        ),
      ],
    );
  }
}

class FormBanner extends StatelessWidget {
  const FormBanner.error(this.message, {super.key}) : isError = true;

  const FormBanner.success(this.message, {super.key}) : isError = false;

  final String message;
  final bool isError;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final background = isError ? scheme.errorContainer : scheme.secondaryContainer;
    final foreground = isError ? scheme.onErrorContainer : scheme.onSecondaryContainer;

    return DecoratedBox(
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(BrieflyRadii.sm),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        child: Row(
          children: [
            Icon(isError ? Icons.error_outline : Icons.check_circle_outline, size: 18, color: foreground),
            const SizedBox(width: 8),
            Expanded(
              child: Text(message, style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: foreground)),
            ),
          ],
        ),
      ),
    );
  }
}

class LoadingFilledButton extends StatelessWidget {
  const LoadingFilledButton({
    super.key,
    required this.onPressed,
    required this.label,
    this.loading = false,
  });

  final VoidCallback? onPressed;
  final String label;
  final bool loading;

  @override
  Widget build(BuildContext context) {
    return IgnorePointer(
      ignoring: loading,
      child: FilledButton(
        onPressed: onPressed,
        child: loading
            ? SizedBox(
                width: 22,
                height: 22,
                child: CircularProgressIndicator(
                  strokeWidth: 2.4,
                  color: Theme.of(context).colorScheme.onPrimary,
                ),
              )
            : Text(label),
      ),
    );
  }
}

class StatChip extends StatelessWidget {
  const StatChip({super.key, required this.label, required this.value});

  final String label;
  final int value;

  @override
  Widget build(BuildContext context) {
    return Chip(label: Text('$label · $value'));
  }
}

class TaskStatsChips extends StatelessWidget {
  const TaskStatsChips({super.key, required this.stats});

  final Map<String, int> stats;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: [
        StatChip(label: l10n.inProgress, value: stats['in_progress'] ?? 0),
        StatChip(label: l10n.blocked, value: stats['blocked'] ?? 0),
        StatChip(label: l10n.statusDone, value: stats['done'] ?? 0),
      ],
    );
  }
}

class InitialAvatar extends StatelessWidget {
  const InitialAvatar({super.key, required this.name, this.radius = 20});

  final String name;
  final double radius;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final trimmed = name.trim();
    final initial = trimmed.isEmpty ? '?' : trimmed.substring(0, 1).toUpperCase();

    return CircleAvatar(
      radius: radius,
      backgroundColor: scheme.secondaryContainer,
      foregroundColor: scheme.onSecondaryContainer,
      child: Text(initial, style: Theme.of(context).textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w700)),
    );
  }
}

class UserHeaderCard extends StatelessWidget {
  const UserHeaderCard({super.key, required this.name, this.jobTitle});

  final String name;
  final String? jobTitle;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return BrieflyCard(
      child: Row(
        children: [
          InitialAvatar(name: name, radius: 28),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                ),
                if (jobTitle != null)
                  Text(
                    jobTitle!,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// One row in a list (task, person, team, ...).
class BrieflyListTile extends StatelessWidget {
  const BrieflyListTile({
    super.key,
    required this.title,
    this.subtitle,
    this.leading,
    this.trailing,
    this.onTap,
    this.grouped = false,
  });

  final String title;
  final String? subtitle;
  final Widget? leading;
  final Widget? trailing;
  final VoidCallback? onTap;
  final bool grouped;

  @override
  Widget build(BuildContext context) {
    final tile = ListTile(
      leading: leading,
      title: Text(title, maxLines: 1, overflow: TextOverflow.ellipsis),
      subtitle: subtitle == null || subtitle!.isEmpty
          ? null
          : Text(subtitle!, maxLines: 1, overflow: TextOverflow.ellipsis),
      trailing: trailing,
      onTap: onTap,
    );

    if (grouped) {
      return tile;
    }

    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Card(child: tile),
    );
  }
}

class TaskListTile extends StatelessWidget {
  const TaskListTile({
    super.key,
    required this.title,
    this.subtitle,
    required this.status,
    this.onTap,
    this.grouped = false,
  });

  final String title;
  final String? subtitle;
  final String status;
  final VoidCallback? onTap;
  final bool grouped;

  @override
  Widget build(BuildContext context) {
    return BrieflyListTile(
      title: title,
      subtitle: subtitle,
      trailing: StatusPill(status: status),
      onTap: onTap,
      grouped: grouped,
    );
  }
}

class RateListTile extends StatelessWidget {
  const RateListTile({
    super.key,
    required this.name,
    required this.rate,
    this.onTap,
    this.grouped = false,
  });

  final String name;
  final int rate;
  final VoidCallback? onTap;
  final bool grouped;

  @override
  Widget build(BuildContext context) {
    return BrieflyListTile(
      title: name,
      trailing: Text(
        '$rate%',
        style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
      ),
      onTap: onTap,
      grouped: grouped,
    );
  }
}

class EntityListTile extends StatelessWidget {
  const EntityListTile({
    super.key,
    required this.title,
    this.subtitle,
    this.leading,
    this.trailing,
    this.onTap,
  });

  final String title;
  final String? subtitle;
  final Widget? leading;
  final Widget? trailing;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return BrieflyListTile(
      title: title,
      subtitle: subtitle,
      leading: leading,
      trailing: trailing ?? const Icon(Icons.chevron_right),
      onTap: onTap,
    );
  }
}

/// Bottom bar around every signed-in screen.
class AppShell extends ConsumerWidget {
  const AppShell({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final user = ref.watch(authProvider).user;
    final location = GoRouterState.of(context).uri.path;
    final destinations = _destinations(l10n, user);

    final selected = destinations.lastIndexWhere(
      (item) => location == item.path || location.startsWith('${item.path}/'),
    );
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

  List<({String path, String label, IconData icon, IconData selectedIcon})> _destinations(
    AppLocalizations l10n,
    AppUser? user,
  ) {
    if (user?.isAdmin == true) {
      return [
        (path: '/admin', label: l10n.navOverview, icon: Icons.pie_chart_outline, selectedIcon: Icons.pie_chart),
        (path: '/admin/projects', label: l10n.navProjects, icon: Icons.folder_outlined, selectedIcon: Icons.folder),
        (path: '/admin/teams', label: l10n.navTeams, icon: Icons.groups_outlined, selectedIcon: Icons.groups),
        (path: '/admin/users', label: l10n.navUsers, icon: Icons.people_outline, selectedIcon: Icons.people),
        (path: '/profile', label: l10n.navProfile, icon: Icons.person_outline, selectedIcon: Icons.person),
      ];
    }

    if (user?.isLead == true) {
      return [
        (path: '/dashboard', label: l10n.navDashboard, icon: Icons.home_outlined, selectedIcon: Icons.home),
        (path: '/tasks', label: l10n.navTasks, icon: Icons.checklist_outlined, selectedIcon: Icons.checklist),
        (path: '/team/tasks', label: l10n.navTeamTasks, icon: Icons.groups_outlined, selectedIcon: Icons.groups),
        (path: '/projects', label: l10n.navProjects, icon: Icons.folder_outlined, selectedIcon: Icons.folder),
        (path: '/profile', label: l10n.navProfile, icon: Icons.person_outline, selectedIcon: Icons.person),
      ];
    }

    return [
      (path: '/dashboard', label: l10n.navDashboard, icon: Icons.home_outlined, selectedIcon: Icons.home),
      (path: '/tasks', label: l10n.navTasks, icon: Icons.checklist_outlined, selectedIcon: Icons.checklist),
      (path: '/profile', label: l10n.navProfile, icon: Icons.person_outline, selectedIcon: Icons.person),
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

  final List<({String path, String label, IconData icon, IconData selectedIcon})> destinations;
  final int selectedIndex;
  final ValueChanged<int> onSelected;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final compact = destinations.length >= 5;

    return Material(
      color: scheme.surface,
      elevation: 0,
      child: DecoratedBox(
        decoration: BoxDecoration(
          border: Border(top: BorderSide(color: scheme.outlineVariant)),
        ),
        child: SafeArea(
          top: false,
          child: SizedBox(
            height: compact ? 60 : 64,
            child: Row(
              children: [
                for (var i = 0; i < destinations.length; i++)
                  Expanded(
                    child: _NavItem(
                      icon: destinations[i].icon,
                      selectedIcon: destinations[i].selectedIcon,
                      label: destinations[i].label,
                      selected: i == selectedIndex,
                      compact: compact,
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
    required this.selectedIcon,
    required this.label,
    required this.selected,
    required this.compact,
    required this.onTap,
  });

  final IconData icon;
  final IconData selectedIcon;
  final String label;
  final bool selected;
  final bool compact;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final color = selected ? scheme.onSurface : scheme.onSurfaceVariant;
    final selectedBg = scheme.secondaryContainer;

    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: EdgeInsets.symmetric(horizontal: compact ? 2 : 4),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            AnimatedContainer(
              duration: const Duration(milliseconds: 180),
              padding: EdgeInsets.symmetric(horizontal: compact ? 10 : 14, vertical: 4),
              decoration: BoxDecoration(
                color: selected ? selectedBg : Colors.transparent,
                borderRadius: BorderRadius.circular(20),
              ),
              child: Icon(selected ? selectedIcon : icon, size: compact ? 20 : 22, color: color),
            ),
            const SizedBox(height: 4),
            FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(
                label,
                maxLines: 1,
                softWrap: false,
                style: Theme.of(context).textTheme.labelSmall?.copyWith(
                      fontSize: compact ? 10 : 11,
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
