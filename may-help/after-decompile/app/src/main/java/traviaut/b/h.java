package traviaut.b;

import java.io.ByteArrayInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.util.concurrent.TimeUnit;
import org.w3c.dom.Element;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/h.class */
public final class h {
    private Element a;

    public static h a(InputStream inputStream) {
        return new h(traviaut.m.a().a(inputStream, System.out).getDocumentElement());
    }

    public static h a(String str) throws i {
        try {
            ByteArrayInputStream byteArrayInputStream = new ByteArrayInputStream(str.getBytes("UTF-8"));
            try {
                h hVarA = a(byteArrayInputStream);
                byteArrayInputStream.close();
                return hVarA;
            } finally {
            }
        } catch (IOException e) {
            throw new i("failed to parse string", e);
        }
    }

    private h(Element element) {
        this.a = element;
    }

    public final boolean a() {
        return a("div", "class", "login") == null;
    }

    public final boolean b() {
        if (!a()) {
            return false;
        }
        if (a("div", "class", "dname") != null || a("div", "id", "content") != null) {
            return true;
        }
        for (int i = 0; i < 5; i++) {
            if (a("div", "id", "lmid" + i) != null) {
                return true;
            }
        }
        return false;
    }

    public final Element b(String str) {
        return a("form", "name", str);
    }

    public final Element c() throws i {
        Element elementA = a("form", "name", "snd");
        if (elementA == null) {
            throw new i("no form");
        }
        return elementA;
    }

    public final long d() {
        e eVarA = new e(this.a).a("div", "id", "content").a("table", "class", "under_progress");
        e eVarA2 = eVarA;
        if (eVarA.b()) {
            eVarA2 = new e(this.a).a("div", "id", "content").a("table", "id", "demolish");
        }
        NodeList nodeListB = eVarA2.b("span");
        if (nodeListB == null) {
            return -1L;
        }
        long jMax = -1;
        for (int i = 0; i < nodeListB.getLength(); i++) {
            Element element = (Element) nodeListB.item(i);
            long millis = element.getAttribute("class").equals("timer") ? TimeUnit.SECONDS.toMillis(traviaut.m.c(element.getAttribute("value"))) : -1L;
            if (element.getAttribute("id").startsWith("timer")) {
                millis = traviaut.m.b(a(element));
            }
            jMax = Math.max(jMax, millis);
        }
        return jMax;
    }

    public final Element a(String str, String str2, String str3) {
        return a(this.a.getElementsByTagName(str), str2, str3);
    }

    public static Element a(Element element, String str, String str2, String str3) {
        return a(element.getElementsByTagName(str), str2, str3);
    }

    public static Element a(Element element, String str, int i) {
        NodeList elementsByTagName = element.getElementsByTagName(str);
        if (elementsByTagName.getLength() <= i) {
            return null;
        }
        return (Element) elementsByTagName.item(i);
    }

    private static Element a(NodeList nodeList, String str, String str2) {
        for (int i = 0; i < nodeList.getLength(); i++) {
            Element element = (Element) nodeList.item(i);
            for (String str3 : element.getAttribute(str).split(" ")) {
                if (str3.equals(str2)) {
                    return element;
                }
            }
        }
        return null;
    }

    public final Element c(String str) {
        NodeList elementsByTagName = this.a.getElementsByTagName(str);
        if (elementsByTagName.getLength() == 0) {
            return null;
        }
        return (Element) elementsByTagName.item(0);
    }

    public static String a(Node node) {
        String strTrim = node.getNodeValue().trim();
        if (!strTrim.isEmpty()) {
            return strTrim;
        }
        NodeList childNodes = node.getChildNodes();
        for (int i = 0; i < childNodes.getLength(); i++) {
            String strA = a(childNodes.item(i));
            if (!strA.isEmpty()) {
                return strA;
            }
        }
        return "";
    }

    public static String a(Node node, StringBuilder sb) {
        StringBuilder sb2 = sb;
        if (sb == null) {
            sb2 = new StringBuilder();
        }
        String strTrim = node.getNodeValue().trim();
        if (!strTrim.isEmpty()) {
            sb.append(strTrim).append(" ");
        }
        NodeList childNodes = node.getChildNodes();
        for (int i = 0; i < childNodes.getLength(); i++) {
            a(childNodes.item(i), sb2);
        }
        if (sb == null) {
            return sb2.toString();
        }
        return null;
    }
}
