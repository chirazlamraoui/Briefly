import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'l10n/app_localizations.dart';
import 'providers/providers.dart';
import 'router/app_router.dart';
import 'theme/briefly_theme.dart';

class BrieflyApp extends ConsumerWidget {
  const BrieflyApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authProvider);
    if (auth.loading) {
      return MaterialApp(
        theme: BrieflyTheme.light(),
        darkTheme: BrieflyTheme.dark(),
        home: const Scaffold(body: Center(child: CircularProgressIndicator())),
      );
    }

    final router = ref.watch(routerProvider);
    final locale = ref.watch(localeProvider);
    final themeMode = ref.watch(themeModeProvider);

    return MaterialApp.router(
      title: 'Briefly',
      theme: BrieflyTheme.light(),
      darkTheme: BrieflyTheme.dark(),
      themeMode: themeMode,
      locale: locale,
      routerConfig: router,
      localizationsDelegates: const [
        AppLocalizations.delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      supportedLocales: AppLocalizations.supportedLocales,
    );
  }
}
