import 'package:flutter/material.dart';
import 'api_client.dart';

class ArchiveListPage extends StatefulWidget {
  const ArchiveListPage({super.key});
  @override
  State<ArchiveListPage> createState() => _ArchiveListPageState();
}

class _ArchiveListPageState extends State<ArchiveListPage> {
  final search = TextEditingController();
  List<dynamic> rows = [];
  bool loading = true;
  String? error;

  @override
  void initState() { super.initState(); load(); }

  Future<void> load() async {
    setState(() { loading = true; error = null; });
    try {
      final dio = await ApiClient.instance.dio;
      final response = await dio.get('/archives', queryParameters: {'search': search.text.trim()});
      final data = response.data['data'];
      if (!mounted) return;
      setState(() => rows = List<dynamic>.from(data['data'] as List));
    } catch (e) {
      if (mounted) setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Semua Arsip')),
      body: Column(children: [
        Padding(padding: const EdgeInsets.all(12), child: TextField(controller: search, onSubmitted: (_) => load(), decoration: InputDecoration(labelText: 'Cari nomor, judul, perihal, atau kata kunci', suffixIcon: IconButton(onPressed: load, icon: const Icon(Icons.search)), border: const OutlineInputBorder()))),
        if (loading) const LinearProgressIndicator(),
        if (error != null) Padding(padding: const EdgeInsets.all(12), child: Text(error!, style: const TextStyle(color: Colors.red))),
        Expanded(child: RefreshIndicator(onRefresh: load, child: ListView.builder(itemCount: rows.length, itemBuilder: (context, index) {
          final row = Map<String, dynamic>.from(rows[index] as Map);
          return Card(margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 5), child: ListTile(
            leading: const Icon(Icons.description_outlined),
            title: Text(row['title']?.toString() ?? '-'),
            subtitle: Text('${row['archive_number'] ?? '-'}\n${row['type'] ?? '-'} • ${row['status'] ?? '-'}'),
            isThreeLine: true,
          ));
        }))),
      ]),
    );
  }
}
