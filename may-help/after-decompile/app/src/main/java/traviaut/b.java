package traviaut;

import java.io.BufferedInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.Proxy;
import java.net.URL;
import java.net.URLConnection;
import java.util.Iterator;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.concurrent.Semaphore;
import java.util.concurrent.TimeUnit;
import org.w3c.dom.NodeList;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b.class */
public final class b {
    private static final int b = (int) TimeUnit.SECONDS.toMillis(8);
    private static final Semaphore c;
    private final e d;
    private final Map<String, String> e = new LinkedHashMap();
    private String f = "";
    public Proxy a = Proxy.NO_PROXY;

    public b(e eVar) {
        this.d = eVar;
    }

    private void a(URLConnection uRLConnection) {
        StringBuilder sb = new StringBuilder();
        for (Map.Entry<String, String> entry : this.e.entrySet()) {
            sb.append(entry.getKey()).append("=").append(entry.getValue()).append("; ");
        }
        if (sb.length() > 0) {
            uRLConnection.setRequestProperty("Cookie", sb.toString());
        }
    }

    public final void a() {
        this.e.clear();
        this.f = null;
    }

    public static void b() {
        c.acquireUninterruptibly();
    }

    public static void c() {
        c.release();
    }

    public final traviaut.b.h a(String str) throws traviaut.b.i {
        try {
            InputStream inputStreamB = b(str);
            try {
                traviaut.b.h hVarA = traviaut.b.h.a(inputStreamB);
                if (inputStreamB != null) {
                    inputStreamB.close();
                }
                return hVarA;
            } finally {
            }
        } catch (IOException e) {
            throw new traviaut.b.i("failed to read data", e);
        }
    }

    public final traviaut.b.h a(j jVar) throws traviaut.b.i {
        try {
            InputStream inputStreamC = c(jVar);
            try {
                traviaut.b.h hVarA = traviaut.b.h.a(inputStreamC);
                if (inputStreamC != null) {
                    inputStreamC.close();
                }
                return hVarA;
            } finally {
            }
        } catch (IOException e) {
            throw new traviaut.b.i("failed to read data", e);
        }
    }

    private InputStream b(String str) throws IOException {
        return b(c(str));
    }

    private InputStream c(j jVar) throws IOException {
        URLConnection uRLConnectionC = c(jVar.a());
        jVar.a(uRLConnectionC);
        return b(uRLConnectionC);
    }

    public final org.a.c b(j jVar) throws traviaut.b.i {
        jVar.b(this.f);
        try {
            InputStreamReader inputStreamReader = new InputStreamReader(c(jVar));
            Throwable th = null;
            try {
                org.a.c cVar = new org.a.c(new org.a.f(inputStreamReader));
                inputStreamReader.close();
                return cVar;
            } catch (Throwable th2) {
                if (0 != 0) {
                    try {
                        inputStreamReader.close();
                    } catch (Throwable th3) {
                        th.addSuppressed(th3);
                    }
                } else {
                    inputStreamReader.close();
                }
                throw th2;
            }
        } catch (IOException e) {
            throw new traviaut.b.i("failed to read response", e);
        } catch (org.a.b e2) {
            throw new traviaut.b.i("failed to parse JSON", e2);
        }
    }

    static {
        c = new Semaphore(f.c().paused ? 0 : 1);
    }

    protected final void a(traviaut.b.h hVar) {
        NodeList elementsByTagName = hVar.a("html", "", "").getElementsByTagName("script");
        for (int i = 0; i < elementsByTagName.getLength(); i++) {
            String strA = traviaut.b.h.a(elementsByTagName.item(i));
            if (strA.contains("window.ajaxToken")) {
                this.f = strA.split("'")[1];
                return;
            }
        }
    }

    private InputStream b(URLConnection uRLConnection) throws IOException {
        InputStream inputStream = uRLConnection.getInputStream();
        List<String> list = uRLConnection.getHeaderFields().get("Set-Cookie");
        if (list != null) {
            Iterator<String> it = list.iterator();
            while (it.hasNext()) {
                String[] strArrSplit = it.next().split(";")[0].split("=");
                this.e.put(strArrSplit[0], strArrSplit[1]);
            }
        }
        Map<String, List<String>> headerFields = uRLConnection.getHeaderFields();
        List<String> list2 = headerFields.get("location");
        List<String> list3 = list2;
        if (list2 == null) {
            list3 = headerFields.get("Location");
        }
        if (list3 == null) {
            return new BufferedInputStream(inputStream);
        }
        inputStream.close();
        return b(list3.get(0));
    }

    private URLConnection c(String str) throws IOException {
        try {
            Thread.sleep(TimeUnit.SECONDS.toMillis(2L));
        } catch (InterruptedException e) {
            g.a("getParser interrupted", e);
        }
        c.acquireUninterruptibly();
        c.release();
        int iIndexOf = str.indexOf(35);
        if (iIndexOf > 0) {
            str = str.substring(0, iIndexOf);
        }
        if (!str.startsWith("http")) {
            str = this.d.a + str;
        }
        URLConnection uRLConnectionOpenConnection = new URL(str).openConnection(Main.a() ? this.a : Proxy.NO_PROXY);
        uRLConnectionOpenConnection.setConnectTimeout(b);
        uRLConnectionOpenConnection.setReadTimeout(b);
        uRLConnectionOpenConnection.setUseCaches(false);
        uRLConnectionOpenConnection.setRequestProperty("Accept", "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8");
        uRLConnectionOpenConnection.setRequestProperty("Accept-Charset", "utf-8");
        String ua = this.d.b.getUA();
        if (ua.isEmpty()) {
            g.a("WARNING: set your user agent, you can find out at http://whatsmyuseragent.com/");
        }
        uRLConnectionOpenConnection.setRequestProperty("User-Agent", ua);
        a(uRLConnectionOpenConnection);
        ((HttpURLConnection) uRLConnectionOpenConnection).setInstanceFollowRedirects(false);
        return uRLConnectionOpenConnection;
    }
}
