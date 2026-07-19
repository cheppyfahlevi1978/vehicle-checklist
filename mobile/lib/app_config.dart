import 'package:shared_preferences/shared_preferences.dart';

class AppConfig {
  static const defaultBaseUrl = 'https://wa.ias4u.my.id/api/mobile/v1';
  static const _key = 'server_base_url';

  static Future<String> getBaseUrl() async {
    final prefs = await SharedPreferences.getInstance();
    return (prefs.getString(_key) ?? defaultBaseUrl).replaceAll(RegExp(r'/$'), '');
  }

  static Future<void> saveBaseUrl(String value) async {
    final uri = Uri.tryParse(value.trim());
    if (uri == null || uri.scheme != 'https' || uri.host.isEmpty) {
      throw const FormatException('Alamat server harus URL HTTPS yang valid.');
    }
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_key, value.trim().replaceAll(RegExp(r'/$'), ''));
  }
}
