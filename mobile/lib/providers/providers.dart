import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../core/api_client.dart';
import '../core/api_exception.dart';
import '../core/token_storage.dart';
import '../models/models.dart';

final tokenStorageProvider = Provider<TokenStorage>((ref) => TokenStorage());

final apiClientProvider = Provider<ApiClient>((ref) {
  return ApiClient(tokenStorage: ref.watch(tokenStorageProvider));
});

class AuthState {
  const AuthState({this.user, this.loading = true});

  final AppUser? user;
  final bool loading;

  bool get isAuthenticated => user != null;
}

class AuthNotifier extends Notifier<AuthState> {
  @override
  AuthState build() {
    Future<void>.microtask(restore);
    return const AuthState();
  }

  Future<void> restore() async {
    final token = await ref.read(tokenStorageProvider).read();
    if (token == null || token.isEmpty) {
      state = const AuthState(loading: false);
      return;
    }

    try {
      final user = await ref.read(apiClientProvider).profile();
      state = AuthState(user: user, loading: false);
    } on ApiException {
      await ref.read(tokenStorageProvider).clear();
      state = const AuthState(loading: false);
    }
  }

  Future<void> login(String email, String password) async {
    final result = await ref.read(apiClientProvider).login(email, password);
    await ref.read(tokenStorageProvider).write(result.token);
    state = AuthState(user: result.user, loading: false);
  }

  Future<void> refreshUser() async {
    final user = await ref.read(apiClientProvider).profile();
    state = AuthState(user: user, loading: false);
  }

  Future<void> logout() async {
    try {
      await ref.read(apiClientProvider).logout();
    } on ApiException {
      // Token is cleared locally either way.
    }
    await ref.read(tokenStorageProvider).clear();
    state = const AuthState(loading: false);
  }
}

final authProvider = NotifierProvider<AuthNotifier, AuthState>(AuthNotifier.new);

class LocaleNotifier extends Notifier<Locale> {
  static const _key = 'briefly-locale';

  @override
  Locale build() {
    Future<void>.microtask(_load);
    return const Locale('fr');
  }

  Future<void> _load() async {
    final prefs = await SharedPreferences.getInstance();
    final code = prefs.getString(_key);
    if (code != null) {
      state = Locale(code);
    }
  }

  Future<void> setLocale(Locale locale) async {
    state = locale;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_key, locale.languageCode);
  }
}

final localeProvider = NotifierProvider<LocaleNotifier, Locale>(LocaleNotifier.new);

class ThemeNotifier extends Notifier<ThemeMode> {
  static const _key = 'briefly-theme';

  @override
  ThemeMode build() {
    Future<void>.microtask(_load);
    return ThemeMode.light;
  }

  Future<void> _load() async {
    final prefs = await SharedPreferences.getInstance();
    state = prefs.getString(_key) == 'dark' ? ThemeMode.dark : ThemeMode.light;
  }

  Future<void> setMode(ThemeMode mode) async {
    state = mode;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_key, mode == ThemeMode.dark ? 'dark' : 'light');
  }
}

final themeModeProvider = NotifierProvider<ThemeNotifier, ThemeMode>(ThemeNotifier.new);
