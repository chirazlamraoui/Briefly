import 'package:briefly_mobile/app.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:briefly_mobile/core/api_client.dart';
import 'package:briefly_mobile/core/token_storage.dart';
import 'package:briefly_mobile/l10n/app_localizations.dart';
import 'package:briefly_mobile/providers/providers.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

class _EmptyTokenStorage extends TokenStorage {
  @override
  Future<String?> read() async => null;

  @override
  Future<void> write(String token) async {}

  @override
  Future<void> clear() async {}
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  GoogleFonts.config.allowRuntimeFetching = false;

  testWidgets('unauthenticated app shows the sign-in screen', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          tokenStorageProvider.overrideWithValue(_EmptyTokenStorage()),
          apiClientProvider.overrideWith((ref) => ApiClient(tokenStorage: ref.watch(tokenStorageProvider))),
        ],
        child: const BrieflyApp(),
      ),
    );

    await tester.pump();
    await tester.pump(const Duration(milliseconds: 50));

    final l10n = await AppLocalizations.delegate.load(const Locale('fr'));
    expect(find.text(l10n.signIn), findsWidgets);
    expect(find.byType(TextField), findsNWidgets(2));
  });
}
