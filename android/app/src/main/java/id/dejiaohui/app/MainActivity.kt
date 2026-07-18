package id.dejiaohui.app

import android.app.Activity
import android.app.DownloadManager
import android.content.Context
import android.content.Intent
import android.net.ConnectivityManager
import android.net.NetworkCapabilities
import android.net.Uri
import android.os.Bundle
import android.os.Environment
import android.provider.Settings
import android.webkit.CookieManager
import android.webkit.DownloadListener
import android.webkit.ValueCallback
import android.webkit.WebChromeClient
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.Toast

class MainActivity : Activity() {
    private lateinit var webView: WebView
    private var uploadCallback: ValueCallback<Array<Uri>>? = null
    private val fileChooserRequest = 2101

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        webView = WebView(this)
        setContentView(webView)

        configureWebView()

        if (savedInstanceState == null) {
            if (hasInternetConnection()) {
                webView.loadUrl(BuildConfig.SERVER_URL)
            } else {
                showOfflinePage()
            }
        } else {
            webView.restoreState(savedInstanceState)
        }
    }

    private fun configureWebView() {
        CookieManager.getInstance().setAcceptCookie(true)
        CookieManager.getInstance().setAcceptThirdPartyCookies(webView, true)

        webView.settings.apply {
            javaScriptEnabled = true
            domStorageEnabled = true
            databaseEnabled = true
            allowFileAccess = true
            allowContentAccess = true
            cacheMode = WebSettings.LOAD_DEFAULT
            mixedContentMode = WebSettings.MIXED_CONTENT_NEVER_ALLOW
            userAgentString = "$userAgentString SIYADI-Android/1.0.0"
        }

        webView.webViewClient = object : WebViewClient() {
            override fun shouldOverrideUrlLoading(view: WebView, request: WebResourceRequest): Boolean {
                val uri = request.url
                val appHost = Uri.parse(BuildConfig.SERVER_URL).host
                return if (uri.host == appHost || uri.scheme == "about") {
                    false
                } else {
                    startActivity(Intent(Intent.ACTION_VIEW, uri))
                    true
                }
            }

            override fun onReceivedError(
                view: WebView,
                request: WebResourceRequest,
                error: android.webkit.WebResourceError
            ) {
                if (request.isForMainFrame) {
                    showOfflinePage()
                }
            }
        }

        webView.webChromeClient = object : WebChromeClient() {
            override fun onShowFileChooser(
                webView: WebView?,
                filePathCallback: ValueCallback<Array<Uri>>?,
                fileChooserParams: FileChooserParams?
            ): Boolean {
                uploadCallback?.onReceiveValue(null)
                uploadCallback = filePathCallback

                val intent = fileChooserParams?.createIntent() ?: Intent(Intent.ACTION_GET_CONTENT).apply {
                    addCategory(Intent.CATEGORY_OPENABLE)
                    type = "*/*"
                }

                return try {
                    startActivityForResult(intent, fileChooserRequest)
                    true
                } catch (exception: Exception) {
                    uploadCallback = null
                    Toast.makeText(this@MainActivity, "Pemilih berkas tidak tersedia.", Toast.LENGTH_LONG).show()
                    false
                }
            }
        }

        webView.setDownloadListener(DownloadListener { url, userAgent, contentDisposition, mimeType, _ ->
            try {
                val request = DownloadManager.Request(Uri.parse(url)).apply {
                    setMimeType(mimeType)
                    addRequestHeader("User-Agent", userAgent)
                    addRequestHeader("Cookie", CookieManager.getInstance().getCookie(url))
                    setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
                    setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, guessFilename(contentDisposition))
                }
                val manager = getSystemService(Context.DOWNLOAD_SERVICE) as DownloadManager
                manager.enqueue(request)
                Toast.makeText(this, "Unduhan dimulai.", Toast.LENGTH_SHORT).show()
            } catch (exception: Exception) {
                startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
            }
        })
    }

    private fun guessFilename(contentDisposition: String?): String {
        val match = Regex("filename=\\\"?([^\\\";]+)", RegexOption.IGNORE_CASE).find(contentDisposition.orEmpty())
        return match?.groupValues?.getOrNull(1) ?: "dejiaohui-download-${System.currentTimeMillis()}"
    }

    private fun hasInternetConnection(): Boolean {
        val manager = getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager
        val network = manager.activeNetwork ?: return false
        val capabilities = manager.getNetworkCapabilities(network) ?: return false
        return capabilities.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET)
    }

    private fun showOfflinePage() {
        val html = """
            <html><head><meta name='viewport' content='width=device-width,initial-scale=1'>
            <style>body{font-family:sans-serif;background:#fffaf0;color:#26211f;display:grid;place-items:center;height:100vh;margin:0}.box{text-align:center;padding:28px}h1{color:#741f2f}button{background:#741f2f;color:white;border:0;border-radius:10px;padding:12px 18px;font-weight:bold}</style></head>
            <body><div class='box'><h1>Koneksi Tidak Tersedia</h1><p>Periksa internet lalu tekan tombol di bawah.</p><button onclick="location.href='${BuildConfig.SERVER_URL}'">Coba Lagi</button></div></body></html>
        """.trimIndent()
        webView.loadDataWithBaseURL(BuildConfig.SERVER_URL, html, "text/html", "UTF-8", null)
    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        if (requestCode == fileChooserRequest) {
            val result = if (resultCode == RESULT_OK) {
                WebChromeClient.FileChooserParams.parseResult(resultCode, data)
            } else {
                null
            }
            uploadCallback?.onReceiveValue(result)
            uploadCallback = null
            return
        }
        super.onActivityResult(requestCode, resultCode, data)
    }

    override fun onSaveInstanceState(outState: Bundle) {
        webView.saveState(outState)
        super.onSaveInstanceState(outState)
    }

    @Deprecated("Deprecated in Java")
    override fun onBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack()
        } else {
            super.onBackPressed()
        }
    }
}
