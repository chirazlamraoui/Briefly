import 'package:briefly_mobile/models/models.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('blocked status requires a blocker note', () {
    expect(validateBlockerNote('BLOCKED', ''), 'blocker');
    expect(validateBlockerNote('BLOCKED', '   '), 'blocker');
    expect(validateBlockerNote('BLOCKED', 'Waiting on API'), isNull);
  });

  test('other statuses do not require a blocker note', () {
    expect(validateBlockerNote('TODO', ''), isNull);
    expect(validateBlockerNote('IN_PROGRESS', ''), isNull);
    expect(validateBlockerNote('DONE', ''), isNull);
  });
}
