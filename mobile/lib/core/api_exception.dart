/// Laravel sent an error (422 validation, 403 forbidden, network, ...).
class ApiException implements Exception {
  ApiException(this.message, {this.fieldErrors = const {}});

  final String message;
  final Map<String, List<String>> fieldErrors;

  String? firstFieldError(String field) {
    final errors = fieldErrors[field];
    if (errors == null || errors.isEmpty) {
      return null;
    }

    return errors.first;
  }

  @override
  String toString() => message;
}
