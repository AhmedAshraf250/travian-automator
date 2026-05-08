package traviaut.xml;

import traviaut.Main;
import traviaut.e.b;
import traviaut.f;

/* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TAAcc.class */
public class TAAcc {
    public String name;
    public String useragent;
    public int nx;
    public int ny;
    public TAHero hero;

    public TAAcc() {
        this.name = "";
        this.useragent = "";
        this.hero = new TAHero();
    }

    public TAAcc(String str) {
        this.name = "";
        this.useragent = "";
        this.hero = new TAHero();
        this.name = str;
    }

    public boolean isNewVill() {
        return (this.nx == 0 && this.ny == 0) ? false : true;
    }

    public void resetVill() {
        this.ny = 0;
        this.nx = 0;
    }

    public String getUA() {
        if (!Main.a()) {
            return f.c().useragent;
        }
        if (f.c().generateua && this.useragent.isEmpty()) {
            this.useragent = b.a();
        }
        return this.useragent.isEmpty() ? f.c().useragent : this.useragent;
    }
}
