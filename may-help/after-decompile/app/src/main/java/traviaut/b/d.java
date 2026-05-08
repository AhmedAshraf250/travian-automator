package traviaut.b;

import traviaut.b.a;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/d.class */
public final class d {
    private final String e;
    public final int a;
    public final int b;
    public final int c;
    public int d;

    public d() {
        this.d = 10;
        this.e = "";
        this.a = 0;
        this.b = 0;
        this.c = 0;
    }

    public d(String str, int i, int i2, int i3) {
        this.d = 10;
        this.e = str;
        this.a = i;
        this.b = i2;
        this.c = i3;
    }

    public final String a() {
        return this.e + " " + this.c;
    }

    private a.C0001a a(traviaut.e eVar) {
        return eVar.e.b()[this.b];
    }

    public final l a(traviaut.e eVar, boolean z) {
        return a(eVar).a(this.c + 1);
    }

    public final boolean a(traviaut.e eVar, l lVar) {
        return a(eVar, true).e(lVar);
    }

    public final boolean b() {
        return this.b != 0;
    }

    public final boolean c() {
        return this.b > 0 && this.b < 5;
    }

    public final boolean b(traviaut.e eVar, boolean z) {
        return !(c() && z) && a(eVar).a == this.c;
    }

    public final boolean a(d dVar) {
        if (!this.e.isEmpty() && this.c == dVar.c - 1) {
            return dVar.e.startsWith(this.e);
        }
        return false;
    }

    public final d d() {
        return new d(this.e, this.a, this.b, this.c + 1);
    }
}
