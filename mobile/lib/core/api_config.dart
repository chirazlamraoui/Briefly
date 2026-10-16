/// Where the phone calls Laravel (the same site as the browser).
///
/// The default is the deployed Alwaysdata site, so a release install
/// talks to that server without a laptop nearby.
///
/// A local server is still possible at build time:
/// `--dart-define=API_BASE_URL=http://10.x.x.x:8000`
class ApiConfig {
  static const String deployedBaseUrl = 'https://lamraoui.alwaysdata.net';

  static String get baseUrl {
    const fromEnv = String.fromEnvironment('API_BASE_URL');
    if (fromEnv.isNotEmpty) {
      return fromEnv;
    }

    return deployedBaseUrl;
  }
}
