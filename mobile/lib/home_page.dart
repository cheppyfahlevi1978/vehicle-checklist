import 'package:flutter/material.dart';
import 'api_client.dart';
import 'archive_list_page.dart';
import 'auth_service.dart';
import 'login_page.dart';
import 'upload_archive_page.dart';

class HomePage extends StatefulWidget {
  const HomePage({super.key});
  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  Map<String, dynamic>? dashboard;
  String? error;

  @override
  void initState() { super.initState(); load(); }

  Future<void> load() async {
    try {
      final dio = await ApiClient.instance.dio;
      final response = await dio.get('/dashboard');
      if (!mounted) return;
      setState(() { dashboard = Map<String, dynamic>.from(response.data['data'] as Map); error = null; });
    } catch (e) {
      if (mounted) setState(() => error = e.toString());
    }
  }

  Future<void> logout() async {
    await AuthService().logout();
    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(MaterialPageRoute(builder: (_) => const LoginPage()), (_) => false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('eArsip'), actions: [IconButton(onPressed: logout, icon: const Icon(Icons.logout))]),
      body: RefreshIndicator(
        onRefresh: load,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (error != null) Card(child: Padding(padding: const EdgeInsets.all(16), child: Text(error!, style: const TextStyle(color: Colors.red)))),
            if (dashboard == null) const Center(child: Padding(padding: EdgeInsets.all(32), child: CircularProgressIndicator())),
            if (dashboard != null) Wrap(
              spacing: 12,
              runSpacing: 12,
              children: dashboard!.entries.map((e) => SizedBox(
                width: 170,
                child: Card(child: Padding(padding: const EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(e.key.replaceAll('_', ' ').toUpperCase(), style: Theme.of(context).textTheme.labelMedium), const SizedBox(height: 8), Text('${e.value}', style: Theme.of(context).textTheme.headlineMedium)]))),
              )).toList(),
            ),
            const SizedBox(height: 16),
            Card(child: ListTile(leading: const Icon(Icons.folder_copy_outlined), title: const Text('Semua Arsip'), subtitle: const Text('Cari dan lihat arsip sesuai hak akses.'), onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ArchiveListPage())))),
            Card(child: ListTile(leading: const Icon(Icons.document_scanner_outlined), title: const Text('Registrasi dan Unggah'), subtitle: const Text('Unggah PDF, Word, Excel, atau hasil kamera.'), onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const UploadArchivePage())))),
            const Card(child: ListTile(leading: Icon(Icons.assignment_turned_in_outlined), title: Text('Disposisi'), subtitle: Text('Endpoint disposisi sudah tersedia pada backend pilot.'))),
            const Card(child: ListTile(leading: Icon(Icons.qr_code_scanner), title: Text('Scan QR Lokasi'), subtitle: Text('Dependency scanner tersedia untuk pengembangan lokasi arsip fisik.'))),
          ],
        ),
      ),
    );
  }
}
