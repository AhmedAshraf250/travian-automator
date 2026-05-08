package traviaut.b;

import org.w3c.dom.Element;
import org.w3c.dom.NodeList;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/e.class */
public final class e {
    private Element a;

    public e(Element element) {
        this.a = element;
    }

    public final e a(String str) {
        return a(str, 0);
    }

    public final e a(String str, int i) {
        return this.a == null ? this : a(this.a.getElementsByTagName(str), i);
    }

    public final e a(String str, String str2, String str3) {
        if (this.a != null) {
            this.a = h.a(this.a, str, str2, str3);
        }
        return this;
    }

    public final NodeList b(String str) {
        if (this.a == null) {
            return null;
        }
        return this.a.getElementsByTagName(str);
    }

    public final e a() {
        if (this.a != null) {
            this.a = (Element) this.a.getParentNode();
        }
        return this;
    }

    public final e a(int i) {
        return this.a == null ? this : a(this.a.getChildNodes(), 1);
    }

    private e a(NodeList nodeList, int i) {
        if (nodeList.getLength() <= i) {
            this.a = null;
        } else {
            this.a = (Element) nodeList.item(i);
        }
        return this;
    }

    public final boolean b() {
        return this.a == null;
    }

    public final String c(String str) {
        if (this.a == null) {
            return null;
        }
        return this.a.getAttribute(str);
    }

    public final String c() {
        if (this.a == null) {
            return null;
        }
        return h.a(this.a);
    }
}
