package org.w3c.tidy;

import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.Reader;
import java.io.UnsupportedEncodingException;

/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/L.class */
public class L {
    private int[] a;
    private int b;
    private Reader c;
    private boolean d;
    private boolean e;
    private int f;
    private int g;
    private int h;
    private int i;
    private int j;

    public int a() {
        return this.f;
    }

    public int b() {
        return this.h;
    }

    public int c() {
        if (this.e) {
            int[] iArr = this.a;
            int i = this.b - 1;
            this.b = i;
            int i2 = iArr[i];
            if (this.b == 0) {
                this.e = false;
            }
            if (i2 != 10) {
                this.f++;
                return i2;
            }
            this.f = 1;
            this.h++;
            return i2;
        }
        this.g = this.f;
        if (this.j > 0) {
            this.f++;
            this.j--;
            return 32;
        }
        int iE = e();
        if (iE < 0) {
            this.d = true;
            return -1;
        }
        if (iE == 10) {
            this.f = 1;
            this.h++;
            return iE;
        }
        if (iE != 13) {
            if (iE != 9) {
                this.f++;
                return iE;
            }
            this.j = (this.i - ((this.f - 1) % this.i)) - 1;
            this.f++;
            return 32;
        }
        int iE2 = e();
        int i3 = iE2;
        if (iE2 != 10) {
            if (i3 != -1) {
                a(i3);
            }
            i3 = 10;
        }
        this.f = 1;
        this.h++;
        return i3;
    }

    public void a(int i) {
        this.e = true;
        if (this.b >= 5) {
            System.arraycopy(this.a, 0, this.a, 1, 4);
            this.b--;
        }
        int[] iArr = this.a;
        int i2 = this.b;
        this.b = i2 + 1;
        iArr[i2] = i;
        if (i == 10) {
            this.h--;
        }
        this.f = this.g;
    }

    public boolean d() {
        return this.d;
    }

    protected L(InputStream inputStream, String str, int i) throws UnsupportedEncodingException {
        this.a = new int[5];
        this.c = new InputStreamReader(inputStream, str);
        this.e = false;
        this.i = i;
        this.h = 1;
        this.f = 1;
        this.d = false;
    }

    public L(Reader reader, int i) {
        this.a = new int[5];
        this.c = null;
        this.e = false;
        this.i = 0;
        this.h = 1;
        this.f = 1;
        this.d = false;
    }

    public int e() {
        try {
            int i = this.c.read();
            if (i < 0) {
                this.d = true;
            }
            return i;
        } catch (IOException unused) {
            this.d = true;
            return -1;
        }
    }
}
