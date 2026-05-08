package traviaut;

import java.io.IOException;
import java.io.OutputStreamWriter;
import java.io.UnsupportedEncodingException;
import java.net.HttpURLConnection;
import java.net.URLConnection;
import java.net.URLEncoder;
import java.util.LinkedHashMap;
import java.util.Map;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;

/* JADX INFO: loaded from: traviaut.jar:traviaut/j.class */
public final class j {
    private final Map<String, String> a = new LinkedHashMap();
    private final Element b;
    private String c;

    public j(String str) {
        a(str);
        this.b = null;
    }

    public j(Element element) {
        this.b = element;
        this.c = element.getAttribute("action");
        a(element.getElementsByTagName("input"));
        a(element.getElementsByTagName("button"));
        this.a.remove("");
    }

    private void a(NodeList nodeList) {
        for (int i = 0; i < nodeList.getLength(); i++) {
            Element element = (Element) nodeList.item(i);
            if (!"radio".equals(element.getAttribute("type")) || "checked".equals(element.getAttribute("checked"))) {
                this.a.put(element.getAttribute("name"), element.getAttribute("value"));
            }
        }
    }

    public final String a() {
        return this.c;
    }

    public final int b() {
        return this.a.size();
    }

    public final void a(String str) {
        this.a.put("cmd", str);
        this.c = "ajax.php?cmd=" + str;
    }

    protected final void a(String str, String str2) {
        b("text", str);
        b("password", str2);
        this.a.remove("autologin");
        this.a.remove("lowRes");
        this.a.put("w", "1360:800");
    }

    private void b(String str, String str2) {
        this.a.put(traviaut.b.h.a(this.b, "input", "type", str).getAttribute("name"), str2);
    }

    public final void a(int i, int i2) {
        this.a.put("x", Integer.toString(i));
        this.a.put("y", Integer.toString(i2));
    }

    public final void a(traviaut.b.l lVar, boolean z) {
        for (int i = 0; i < 4; i++) {
            if (z || lVar.c(i) > 0) {
                this.a.put("r" + (i + 1), lVar.d(i));
            }
        }
        this.a.put("x2", "1");
    }

    public final void b(String str) {
        if (str.isEmpty()) {
            return;
        }
        this.a.put("ajaxToken", str);
    }

    public final void a(boolean[] zArr, String[] strArr, int[] iArr) {
        this.a.remove("saveHeroAttributes");
        for (int i = 0; i < strArr.length; i++) {
            if (zArr[i]) {
                this.a.remove("attribute" + strArr[i]);
                this.a.put("attributes[" + strArr[i] + "]", Integer.toString(iArr[i]));
            }
        }
    }

    public final void b(int i, int i2) {
        this.a.put("t" + i, String.valueOf(i2));
    }

    public final void a(int i) {
        this.a.put("abriss", Integer.toString(i));
    }

    public final void b(int i) {
        this.a.put("exTyp", "SilverToGold");
        this.a.put("s", Integer.toString(i * 200));
        this.a.put("g", Integer.toString(i));
    }

    public final void c(int i) {
        this.a.put("itemTypeId", Integer.toString(i));
    }

    public final void c(int i, int i2) {
        this.a.put("id", Integer.toString(i));
        this.a.put("amount", Integer.toString(i2));
    }

    public final void c(String str) {
        this.a.put("questTutorialId", str);
    }

    public final void d(String str) {
        this.a.put("questTutorialId", str);
        this.a.put("action", "reward");
    }

    public final void a(int i, int i2, int i3) {
        this.a.put("rid2", String.valueOf(i + 1));
        this.a.put("rid1", String.valueOf(i2 + 1));
        this.a.put("m1", String.valueOf(i3));
        this.a.put("m2", String.valueOf(i3));
        this.a.remove("d1");
        this.a.remove("ally");
    }

    private static String e(String str) {
        try {
            return URLEncoder.encode(str, "UTF-8");
        } catch (UnsupportedEncodingException e) {
            g.a("unsupported UTF-8 encoding", e);
            return "";
        }
    }

    public final void a(URLConnection uRLConnection) throws IOException {
        uRLConnection.setDoOutput(true);
        uRLConnection.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
        ((HttpURLConnection) uRLConnection).setRequestMethod("POST");
        if (this.a.containsKey("cmd")) {
            uRLConnection.setRequestProperty("X-Request", "JSON");
            uRLConnection.setRequestProperty("X-Requested-With", "XMLHttpRequest");
            uRLConnection.setRequestProperty("Accept", "text/javascript, text/html, application/xml, text/xml, */*");
        }
        StringBuilder sb = new StringBuilder();
        for (Map.Entry<String, String> entry : this.a.entrySet()) {
            sb.append(e(entry.getKey()));
            sb.append('=');
            sb.append(e(entry.getValue()));
            sb.append('&');
        }
        sb.setLength(sb.length() - 1);
        String string = sb.toString();
        OutputStreamWriter outputStreamWriter = new OutputStreamWriter(uRLConnection.getOutputStream());
        Throwable th = null;
        try {
            outputStreamWriter.write(string);
            outputStreamWriter.flush();
            uRLConnection.connect();
            outputStreamWriter.close();
        } catch (Throwable th2) {
            if (0 != 0) {
                try {
                    outputStreamWriter.close();
                } catch (Throwable th3) {
                    th.addSuppressed(th3);
                }
            } else {
                outputStreamWriter.close();
            }
            throw th2;
        }
    }
}
