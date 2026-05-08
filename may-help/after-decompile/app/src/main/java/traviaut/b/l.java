package traviaut.b;

import java.util.Arrays;

/* JADX INFO: loaded from: traviaut.jar:traviaut/b/l.class */
public final class l {
    private final int[] a;

    /* JADX INFO: loaded from: traviaut.jar:traviaut/b/l$a.class */
    interface a {
        int func(int i, int i2);
    }

    /* JADX INFO: loaded from: traviaut.jar:traviaut/b/l$b.class */
    interface b {
        int func(int i, int i2, int i3);
    }

    public l() {
        this.a = new int[4];
    }

    public l(int[] iArr) {
        this.a = new int[4];
        System.arraycopy(iArr, 0, this.a, 0, Math.min(4, iArr.length));
    }

    public l(int i) {
        this.a = new int[4];
        Arrays.fill(this.a, i);
    }

    public final String toString() {
        return traviaut.m.a(this.a);
    }

    public final boolean a() {
        return this.a[3] < 0;
    }

    public final int b() {
        return this.a[3];
    }

    public final void c() {
        this.a[3] = 0;
    }

    public final void d() {
        this.a[3] = (this.a[3] * 100) / traviaut.f.c().cropFactor;
    }

    public final int[] e() {
        return (int[]) this.a.clone();
    }

    private l a(a aVar) {
        int[] iArr = (int[]) this.a.clone();
        for (int i = 0; i < 4; i++) {
            iArr[i] = aVar.func(i, this.a[i]);
        }
        return new l(iArr);
    }

    private l a(l lVar, b bVar) {
        int[] iArr = (int[]) this.a.clone();
        for (int i = 0; i < 4; i++) {
            iArr[i] = bVar.func(i, this.a[i], lVar.a[i]);
        }
        return new l(iArr);
    }

    public final void a(l lVar) {
        for (int i = 0; i < 4; i++) {
            int[] iArr = this.a;
            int i2 = i;
            iArr[i2] = iArr[i2] + lVar.a[i];
        }
    }

    public final l b(l lVar) {
        return a(lVar, (i, i2, i3) -> {
            return i2 - i3;
        });
    }

    public final l a(int i) {
        return a((i2, i3) -> {
            return i3 * i;
        });
    }

    public final l c(l lVar) {
        return a(lVar, (i, i2, i3) -> {
            return i2 < i3 ? i2 : i3;
        });
    }

    public final l b(int i) {
        int i2 = 1000000;
        return a((i3, i4) -> {
            return i2 < i4 ? i2 : i4;
        });
    }

    public final l d(l lVar) {
        return a(lVar, (i, i2, i3) -> {
            if (i3 > 0) {
                return i2;
            }
            return 0;
        });
    }

    public final int c(int i) {
        return this.a[i];
    }

    public final String d(int i) {
        return String.valueOf(this.a[i]);
    }

    public final boolean e(l lVar) {
        for (int i = 0; i < 4; i++) {
            if (this.a[i] > lVar.a[i]) {
                return false;
            }
        }
        return true;
    }

    public final l f() {
        return a((i, i2) -> {
            if (i2 < 0) {
                return 0;
            }
            return i2;
        });
    }

    public static int e(int i) {
        return a(i, false);
    }

    /* JADX INFO: Access modifiers changed from: private */
    public static int a(int i, boolean z) {
        int i2 = i % 50;
        if (i2 != 0 && z) {
            i += 50;
        }
        return i - i2;
    }

    public final l g() {
        return a((i, i2) -> {
            return a(i2, true);
        });
    }

    public final l h() {
        return a((i, i2) -> {
            return a(i2, false);
        });
    }

    public final int i() {
        int i = 0;
        for (int i2 = 0; i2 < 4; i2++) {
            i += this.a[i2];
        }
        return i;
    }

    public final l f(int i) {
        int iA = a(i, false);
        if (i() <= iA) {
            return this;
        }
        int[] iArr = new int[4];
        int i2 = 4;
        while (i2 > 0 && iA > 0) {
            int iA2 = a(iA / i2, false);
            int i3 = iA2;
            if (iA2 == 0) {
                i3 = 50;
            }
            for (int i4 = 0; i4 < 4; i4++) {
                if (iArr[i4] != this.a[i4]) {
                    int i5 = this.a[i4] - iArr[i4];
                    int i6 = i5;
                    if (i5 > i3) {
                        i6 = i3;
                    }
                    if (i6 > iA) {
                        i6 = iA;
                    }
                    int i7 = i4;
                    iArr[i7] = iArr[i7] + i6;
                    if (iArr[i4] == this.a[i4]) {
                        i2--;
                    }
                    iA -= i6;
                }
            }
        }
        return new l(iArr);
    }

    public final l g(int i) {
        while (true) {
            int i2 = 0;
            int[] iArr = this.a;
            for (int i3 = 0; i3 < 4; i3++) {
                if (iArr[i3] == 0) {
                    i2++;
                }
            }
            int i4 = 4 - i2;
            if (i4 == 0) {
                return this;
            }
            int iA = a((i() - i) / i4, false);
            if (iA <= 0) {
                int i5 = i() - i;
                for (int i6 = 0; i6 < 4 && i5 > 0; i6++) {
                    int[] iArr2 = this.a;
                    int i7 = i6;
                    iArr2[i7] = iArr2[i7] - Math.min(50, this.a[i6]);
                    i5 -= 50;
                }
                return this;
            }
            for (int i8 = 0; i8 < 4; i8++) {
                int[] iArr3 = this.a;
                int i9 = i8;
                iArr3[i9] = iArr3[i9] - Math.min(iA, this.a[i8]);
            }
        }
    }
}
