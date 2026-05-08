package org.w3c.tidy;

/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/F.class */
public final class F {
    private int[] a;
    private int b;
    private int c;
    private int d;
    private boolean e;
    private boolean f;
    private C0009h g;

    public F(C0009h c0009h) {
        this.g = c0009h;
    }

    public static int a(byte[] bArr, int i, int[] iArr) {
        int[] iArr2 = new int[1];
        int[] iArr3 = {0};
        if (x.a(iArr2, S.b(bArr[i]), bArr, null, iArr3, i + 1)) {
            iArr2[0] = 65533;
        }
        iArr[0] = iArr2[0];
        return iArr3[0] - 1;
    }

    private void a(int i, int i2) {
        if (i2 + 1 >= this.b) {
            while (i2 + 1 >= this.b) {
                if (this.b == 0) {
                    this.b = 256;
                } else {
                    this.b <<= 1;
                }
            }
            int[] iArr = new int[this.b];
            if (this.a != null) {
                System.arraycopy(this.a, 0, iArr, 0, i2);
            }
            this.a = iArr;
        }
        this.a[i2] = i;
    }

    private int a(String str, int i) {
        int length = str.length();
        if (i + length >= this.b) {
            while (i + length >= this.b) {
                if (this.b == 0) {
                    this.b = 256;
                } else {
                    this.b <<= 1;
                }
            }
            int[] iArr = new int[this.b];
            if (this.a != null) {
                System.arraycopy(this.a, 0, iArr, 0, i);
            }
            this.a = iArr;
        }
        for (int i2 = 0; i2 < length; i2++) {
            this.a[i + i2] = str.charAt(i2);
        }
        return i + length;
    }

    private void b(D d, int i) {
        if (this.d == 0) {
            return;
        }
        for (int i2 = 0; i2 < i; i2++) {
            d.a(32);
        }
        for (int i3 = 0; i3 < this.d; i3++) {
            d.a(this.a[i3]);
        }
        if (this.f) {
            d.a(32);
            d.a(92);
        }
        d.a();
        if (this.c > this.d) {
            int i4 = 0;
            if (this.a[this.d] == 32) {
                this.d++;
            }
            int i5 = this.d;
            a(0, this.c);
            while (true) {
                this.a[i4] = this.a[i5];
                if (this.a[i5] == 0) {
                    break;
                }
                i4++;
                i5++;
            }
            this.c -= this.d;
        } else {
            this.c = 0;
        }
        this.d = 0;
    }

    private void a(D d, int i, boolean z) {
        for (int i2 = 0; i2 < i; i2++) {
            d.a(32);
        }
        for (int i3 = 0; i3 < this.d; i3++) {
            d.a(this.a[i3]);
        }
        d.a(32);
        if (z) {
            d.a(92);
        }
        d.a();
        if (this.c > this.d) {
            int i4 = 0;
            if (this.a[this.d] == 32) {
                this.d++;
            }
            int i5 = this.d;
            a(0, this.c);
            while (true) {
                this.a[i4] = this.a[i5];
                if (this.a[i5] == 0) {
                    break;
                }
                i4++;
                i5++;
            }
            this.c -= this.d;
        } else {
            this.c = 0;
        }
        this.d = 0;
    }

    public final void a(D d, int i) {
        if (this.c > 0) {
            if (i + this.c >= this.g.b) {
                b(d, i);
            }
            for (int i2 = 0; i2 < i; i2++) {
                d.a(32);
            }
            for (int i3 = 0; i3 < this.c; i3++) {
                d.a(this.a[i3]);
            }
        }
        d.a();
        this.c = 0;
        this.d = 0;
        this.e = false;
    }

    private void c(D d, int i) {
        if (this.c > 0) {
            if (i + this.c >= this.g.b) {
                b(d, i);
            }
            for (int i2 = 0; i2 < i; i2++) {
                d.a(32);
            }
            for (int i3 = 0; i3 < this.c; i3++) {
                d.a(this.a[i3]);
            }
            d.a();
            this.c = 0;
            this.d = 0;
            this.e = false;
        }
    }

    private void a(int i, short s) {
        String strA;
        String strA2;
        boolean z = false;
        if (i == 32 && !S.a(s & 23)) {
            if (S.a(s & 8)) {
                if (this.g.G || this.g.p) {
                    int i2 = this.c;
                    this.c = i2 + 1;
                    a(38, i2);
                    int i3 = this.c;
                    this.c = i3 + 1;
                    a(35, i3);
                    int i4 = this.c;
                    this.c = i4 + 1;
                    a(49, i4);
                    int i5 = this.c;
                    this.c = i5 + 1;
                    a(54, i5);
                    int i6 = this.c;
                    this.c = i6 + 1;
                    a(48, i6);
                    int i7 = this.c;
                    this.c = i7 + 1;
                    a(59, i7);
                    return;
                }
                int i8 = this.c;
                this.c = i8 + 1;
                a(38, i8);
                int i9 = this.c;
                this.c = i9 + 1;
                a(110, i9);
                int i10 = this.c;
                this.c = i10 + 1;
                a(98, i10);
                int i11 = this.c;
                this.c = i11 + 1;
                a(115, i11);
                int i12 = this.c;
                this.c = i12 + 1;
                a(112, i12);
                int i13 = this.c;
                this.c = i13 + 1;
                a(59, i13);
                return;
            }
            this.d = this.c;
        }
        if (S.a(s & 18)) {
            int i14 = this.c;
            this.c = i14 + 1;
            a(i, i14);
            return;
        }
        if (!S.a(s & 16)) {
            if (i == 60) {
                int i15 = this.c;
                this.c = i15 + 1;
                a(38, i15);
                int i16 = this.c;
                this.c = i16 + 1;
                a(108, i16);
                int i17 = this.c;
                this.c = i17 + 1;
                a(116, i17);
                int i18 = this.c;
                this.c = i18 + 1;
                a(59, i18);
                return;
            }
            if (i == 62) {
                int i19 = this.c;
                this.c = i19 + 1;
                a(38, i19);
                int i20 = this.c;
                this.c = i20 + 1;
                a(103, i20);
                int i21 = this.c;
                this.c = i21 + 1;
                a(116, i21);
                int i22 = this.c;
                this.c = i22 + 1;
                a(59, i22);
                return;
            }
            if (i == 38 && this.g.J) {
                int i23 = this.c;
                this.c = i23 + 1;
                a(38, i23);
                int i24 = this.c;
                this.c = i24 + 1;
                a(97, i24);
                int i25 = this.c;
                this.c = i25 + 1;
                a(109, i25);
                int i26 = this.c;
                this.c = i26 + 1;
                a(112, i26);
                int i27 = this.c;
                this.c = i27 + 1;
                a(59, i27);
                return;
            }
            if (i == 34 && this.g.H) {
                int i28 = this.c;
                this.c = i28 + 1;
                a(38, i28);
                int i29 = this.c;
                this.c = i29 + 1;
                a(113, i29);
                int i30 = this.c;
                this.c = i30 + 1;
                a(117, i30);
                int i31 = this.c;
                this.c = i31 + 1;
                a(111, i31);
                int i32 = this.c;
                this.c = i32 + 1;
                a(116, i32);
                int i33 = this.c;
                this.c = i33 + 1;
                a(59, i33);
                return;
            }
            if (i == 39 && this.g.H) {
                int i34 = this.c;
                this.c = i34 + 1;
                a(38, i34);
                int i35 = this.c;
                this.c = i35 + 1;
                a(35, i35);
                int i36 = this.c;
                this.c = i36 + 1;
                a(51, i36);
                int i37 = this.c;
                this.c = i37 + 1;
                a(57, i37);
                int i38 = this.c;
                this.c = i38 + 1;
                a(59, i38);
                return;
            }
            if (i == 160 && !this.g.at) {
                if (this.g.x) {
                    int i39 = this.c;
                    this.c = i39 + 1;
                    a(32, i39);
                    return;
                }
                if (!this.g.I) {
                    int i40 = this.c;
                    this.c = i40 + 1;
                    a(i, i40);
                    return;
                }
                int i41 = this.c;
                this.c = i41 + 1;
                a(38, i41);
                if (this.g.G || this.g.p) {
                    int i42 = this.c;
                    this.c = i42 + 1;
                    a(35, i42);
                    int i43 = this.c;
                    this.c = i43 + 1;
                    a(49, i43);
                    int i44 = this.c;
                    this.c = i44 + 1;
                    a(54, i44);
                    int i45 = this.c;
                    this.c = i45 + 1;
                    a(48, i45);
                } else {
                    int i46 = this.c;
                    this.c = i46 + 1;
                    a(110, i46);
                    int i47 = this.c;
                    this.c = i47 + 1;
                    a(98, i47);
                    int i48 = this.c;
                    this.c = i48 + 1;
                    a(115, i48);
                    int i49 = this.c;
                    this.c = i49 + 1;
                    a(112, i49);
                }
                int i50 = this.c;
                this.c = i50 + 1;
                a(59, i50);
                return;
            }
        }
        if ("UTF8".equals(this.g.c())) {
            if (i >= 8192 && !S.a(s & 1)) {
                if ((i < 8192 || i > 8198) && ((i < 8200 || i > 8208) && ((i < 8209 || i > 8262) && ((i < 8317 || i > 8318) && ((i < 8333 || i > 8334) && ((i < 9001 || i > 9002) && ((i < 12289 || i > 12291) && ((i < 12296 || i > 12305) && ((i < 12308 || i > 12319) && ((i < 64830 || i > 64831) && ((i < 65072 || i > 65092) && ((i < 65097 || i > 65106) && ((i < 65108 || i > 65121) && ((i < 65130 || i > 65131) && ((i < 65281 || i > 65283) && ((i < 65285 || i > 65290) && ((i < 65292 || i > 65295) && ((i < 65306 || i > 65307) && ((i < 65311 || i > 65312) && ((i < 65339 || i > 65341) && (i < 65377 || i > 65381))))))))))))))))))))) {
                    switch (i) {
                        case 12336:
                        case 12539:
                        case 65123:
                        case 65128:
                        case 65343:
                        case 65371:
                        case 65373:
                            this.d = this.c + 2;
                            z = true;
                            break;
                    }
                } else {
                    this.d = this.c + 2;
                    z = true;
                }
                if (z) {
                    if ((i < 8218 || i > 8220) && (i < 8222 || i > 8223)) {
                        switch (i) {
                            case 8216:
                            case 8249:
                            case 8261:
                            case 8317:
                            case 8333:
                            case 9001:
                            case 12296:
                            case 12298:
                            case 12300:
                            case 12302:
                            case 12304:
                            case 12308:
                            case 12310:
                            case 12312:
                            case 12314:
                            case 12317:
                            case 64830:
                            case 65077:
                            case 65079:
                            case 65081:
                            case 65083:
                            case 65085:
                            case 65087:
                            case 65089:
                            case 65091:
                            case 65113:
                            case 65115:
                            case 65117:
                            case 65288:
                            case 65339:
                            case 65371:
                            case 65378:
                                this.d--;
                                break;
                        }
                    } else {
                        this.d--;
                    }
                }
            } else {
                if ("BIG5".equals(this.g.c())) {
                    int i51 = this.c;
                    this.c = i51 + 1;
                    a(i, i51);
                    if ((i & 65280) != 41216 || S.a(s & 1)) {
                        return;
                    }
                    this.d = this.c;
                    if (i <= 92 || i >= 173 || (i & 1) != 1) {
                        return;
                    }
                    this.d--;
                    return;
                }
                if ("SHIFTJIS".equals(this.g.c()) || "ISO2022".equals(this.g.c())) {
                    int i52 = this.c;
                    this.c = i52 + 1;
                    a(i, i52);
                    return;
                } else if (this.g.at) {
                    int i53 = this.c;
                    this.c = i53 + 1;
                    a(i, i53);
                    return;
                }
            }
        }
        if (i == 160 && S.a(s & 1)) {
            int i54 = this.c;
            this.c = i54 + 1;
            a(32, i54);
            return;
        }
        if (((this.g.w && this.g.ai) || this.g.x) && i >= 8211 && i <= 8222) {
            switch (i) {
                case 8211:
                case 8212:
                    i = 45;
                    break;
                case 8216:
                case 8217:
                case 8218:
                    i = 39;
                    break;
                case 8220:
                case 8221:
                case 8222:
                    i = 34;
                    break;
            }
        }
        if ("ISO8859_1".equals(this.g.c())) {
            if (i > 255) {
                String str = (this.g.G || (strA2 = z.a().a((short) i)) == null) ? "&#" + i + ";" : "&" + strA2 + ";";
                for (int i55 = 0; i55 < str.length(); i55++) {
                    char cCharAt = str.charAt(i55);
                    int i56 = this.c;
                    this.c = i56 + 1;
                    a(cCharAt, i56);
                }
                return;
            }
            if (i <= 126 || i >= 160) {
                int i57 = this.c;
                this.c = i57 + 1;
                a(i, i57);
                return;
            }
            String str2 = "&#" + i + ";";
            for (int i58 = 0; i58 < str2.length(); i58++) {
                char cCharAt2 = str2.charAt(i58);
                int i59 = this.c;
                this.c = i59 + 1;
                a(cCharAt2, i59);
            }
            return;
        }
        if (this.g.c().startsWith("UTF")) {
            int i60 = this.c;
            this.c = i60 + 1;
            a(i, i60);
            return;
        }
        if (this.g.p) {
            if (i <= 127 || !"ASCII".equals(this.g.c())) {
                int i61 = this.c;
                this.c = i61 + 1;
                a(i, i61);
                return;
            }
            String str3 = "&#" + i + ";";
            for (int i62 = 0; i62 < str3.length(); i62++) {
                char cCharAt3 = str3.charAt(i62);
                int i63 = this.c;
                this.c = i63 + 1;
                a(cCharAt3, i63);
            }
            return;
        }
        if (!"ASCII".equals(this.g.c()) || (i <= 126 && (i >= 32 || i == 9))) {
            int i64 = this.c;
            this.c = i64 + 1;
            a(i, i64);
            return;
        }
        String str4 = (this.g.G || (strA = z.a().a((short) i)) == null) ? "&#" + i + ";" : "&" + strA + ";";
        for (int i65 = 0; i65 < str4.length(); i65++) {
            char cCharAt4 = str4.charAt(i65);
            int i66 = this.c;
            this.c = i66 + 1;
            a(cCharAt4, i66);
        }
    }

    private void a(D d, short s, int i, byte[] bArr, int i2, int i3) {
        int[] iArr = new int[1];
        int iA = i2;
        while (iA < i3) {
            if (i + this.c >= this.g.b) {
                b(d, i);
            }
            int i4 = bArr[iA] & 255;
            int i5 = i4;
            if (i4 > 127) {
                iA += a(bArr, iA, iArr);
                i5 = iArr[0];
            }
            if (i5 == 10) {
                a(d, i);
            } else {
                a(i5, s);
            }
            iA++;
        }
    }

    private void a(String str) {
        for (int i = 0; i < str.length(); i++) {
            char cCharAt = str.charAt(i);
            int i2 = this.c;
            this.c = i2 + 1;
            a(cCharAt, i2);
        }
    }

    private void a(D d, int i, String str, int i2, boolean z) {
        int[] iArr = new int[1];
        boolean z2 = false;
        short s = z ? (short) 4 : (short) 5;
        byte[] bArrA = str != null ? S.a(str) : null;
        if (bArrA != null && bArrA.length >= 5 && bArrA[0] == 60 && (bArrA[1] == 37 || bArrA[1] == 64 || new String(bArrA, 0, 5).equals("<?php"))) {
            s = (short) (s | 16);
        }
        if (i2 == 0) {
            i2 = 34;
        }
        int i3 = this.c;
        this.c = i3 + 1;
        a(61, i3);
        if (!this.g.q) {
            if (i + this.c < this.g.b) {
                this.d = this.c;
            }
            if (i + this.c >= this.g.b) {
                b(d, i);
            }
            if (i + this.c < this.g.b) {
                this.d = this.c;
            } else {
                c(d, i);
            }
        }
        int i4 = this.c;
        this.c = i4 + 1;
        a(i2, i4);
        if (str != null) {
            this.f = false;
            int iA = 0;
            while (iA < bArrA.length) {
                int i5 = bArrA[iA] & 255;
                if (z && i5 == 32 && i + this.c < this.g.b) {
                    this.d = this.c;
                    z2 = this.f;
                }
                if (z && this.d > 0 && i + this.c >= this.g.b) {
                    a(d, i, z2);
                }
                if (i5 == i2) {
                    String str2 = i5 == 34 ? "&quot;" : "&#39;";
                    for (int i6 = 0; i6 < str2.length(); i6++) {
                        char cCharAt = str2.charAt(i6);
                        int i7 = this.c;
                        this.c = i7 + 1;
                        a(cCharAt, i7);
                    }
                    iA++;
                } else if (i5 == 34) {
                    if (this.g.H) {
                        int i8 = this.c;
                        this.c = i8 + 1;
                        a(38, i8);
                        int i9 = this.c;
                        this.c = i9 + 1;
                        a(113, i9);
                        int i10 = this.c;
                        this.c = i10 + 1;
                        a(117, i10);
                        int i11 = this.c;
                        this.c = i11 + 1;
                        a(111, i11);
                        int i12 = this.c;
                        this.c = i12 + 1;
                        a(116, i12);
                        int i13 = this.c;
                        this.c = i13 + 1;
                        a(59, i13);
                    } else {
                        int i14 = this.c;
                        this.c = i14 + 1;
                        a(34, i14);
                    }
                    if (i2 == 39) {
                        this.f = !this.f;
                    }
                    iA++;
                } else if (i5 == 39) {
                    if (this.g.H) {
                        int i15 = this.c;
                        this.c = i15 + 1;
                        a(38, i15);
                        int i16 = this.c;
                        this.c = i16 + 1;
                        a(35, i16);
                        int i17 = this.c;
                        this.c = i17 + 1;
                        a(51, i17);
                        int i18 = this.c;
                        this.c = i18 + 1;
                        a(57, i18);
                        int i19 = this.c;
                        this.c = i19 + 1;
                        a(59, i19);
                    } else {
                        int i20 = this.c;
                        this.c = i20 + 1;
                        a(39, i20);
                    }
                    if (i2 == 34) {
                        this.f = !this.f;
                    }
                    iA++;
                } else {
                    if (i5 > 127) {
                        iA += a(bArrA, iA, iArr);
                        i5 = iArr[0];
                    }
                    iA++;
                    if (i5 == 10) {
                        a(d, i);
                    } else {
                        a(i5, s);
                    }
                }
            }
        }
        this.f = false;
        int i21 = this.c;
        this.c = i21 + 1;
        a(i2, i21);
    }

    private static boolean a(C c) {
        while (c != null && c.m != null && S.a(c.m.c & 16)) {
            C c2 = c.b;
            if (c2 != null) {
                if (c2.h != 4 || c2.f <= c2.e) {
                    return false;
                }
                int i = c2.g[c2.f - 1] & 255;
                return i == 160 || i == 32 || i == 10;
            }
            c = c.a;
        }
        return true;
    }

    private void a(D d, short s, int i, C c) {
        Q q = this.g.ap;
        int i2 = this.c;
        this.c = i2 + 1;
        a(60, i2);
        if (c.h == 6) {
            int i3 = this.c;
            this.c = i3 + 1;
            a(47, i3);
        }
        String str = c.n;
        for (int i4 = 0; i4 < str.length(); i4++) {
            char cA = S.a(str.charAt(i4), this.g.u, this.g.p);
            int i5 = this.c;
            this.c = i5 + 1;
            a(cA, i5);
        }
        a(d, i, c, c.o);
        if ((this.g.q || this.g.r) && (c.h == 7 || S.a(c.m.c & 1))) {
            int i6 = this.c;
            this.c = i6 + 1;
            a(32, i6);
            int i7 = this.c;
            this.c = i7 + 1;
            a(47, i7);
        }
        int i8 = this.c;
        this.c = i8 + 1;
        a(62, i8);
        if ((c.h == 7 && !this.g.r) || S.a(s & 1)) {
            c(d, i);
            return;
        }
        if (i + this.c >= this.g.b) {
            b(d, i);
        }
        if (i + this.c >= this.g.b || S.a(s & 8)) {
            return;
        }
        if ((!S.a(c.m.c & 16) || c.m == q.B) && a(c)) {
            this.d = this.c;
        }
    }

    private void b(C c) {
        int i = this.c;
        this.c = i + 1;
        a(60, i);
        int i2 = this.c;
        this.c = i2 + 1;
        a(47, i2);
        String str = c.n;
        for (int i3 = 0; i3 < str.length(); i3++) {
            char cA = S.a(str.charAt(i3), this.g.u, this.g.p);
            int i4 = this.c;
            this.c = i4 + 1;
            a(cA, i4);
        }
        int i5 = this.c;
        this.c = i5 + 1;
        a(62, i5);
    }

    private void a(D d, int i, C c) {
        boolean z = this.g.ae;
        if (i + this.c < this.g.b) {
            this.d = this.c;
        }
        int i2 = this.c;
        this.c = i2 + 1;
        a(60, i2);
        int i3 = this.c;
        this.c = i3 + 1;
        a(33, i3);
        int i4 = this.c;
        this.c = i4 + 1;
        a(45, i4);
        int i5 = this.c;
        this.c = i5 + 1;
        a(45, i5);
        a(d, (short) 2, i, c.g, c.e, c.f);
        int i6 = this.c;
        this.c = i6 + 1;
        a(45, i6);
        int i7 = this.c;
        this.c = i7 + 1;
        a(45, i7);
        int i8 = this.c;
        this.c = i8 + 1;
        a(62, i8);
        if (c.k) {
            a(d, i);
        }
    }

    private void b(D d, int i, C c) {
        short s = 0;
        boolean z = this.g.H;
        this.g.H = false;
        if (i + this.c < this.g.b) {
            this.d = this.c;
        }
        c(d, i);
        int i2 = this.c;
        this.c = i2 + 1;
        a(60, i2);
        int i3 = this.c;
        this.c = i3 + 1;
        a(33, i3);
        int i4 = this.c;
        this.c = i4 + 1;
        a(68, i4);
        int i5 = this.c;
        this.c = i5 + 1;
        a(79, i5);
        int i6 = this.c;
        this.c = i6 + 1;
        a(67, i6);
        int i7 = this.c;
        this.c = i7 + 1;
        a(84, i7);
        int i8 = this.c;
        this.c = i8 + 1;
        a(89, i8);
        int i9 = this.c;
        this.c = i9 + 1;
        a(80, i9);
        int i10 = this.c;
        this.c = i10 + 1;
        a(69, i10);
        int i11 = this.c;
        this.c = i11 + 1;
        a(32, i11);
        if (i + this.c < this.g.b) {
            this.d = this.c;
        }
        int iA = c.e;
        while (iA < c.f) {
            if (i + this.c >= this.g.b) {
                b(d, i);
            }
            int i12 = c.g[iA] & 255;
            if (S.a(s & 16)) {
                if (i12 == 93) {
                    s = (short) (s & (-17));
                }
            } else if (i12 == 91) {
                s = (short) (s | 16);
            }
            int[] iArr = new int[1];
            if (i12 > 127) {
                iA += a(c.g, iA, iArr);
                i12 = iArr[0];
            }
            if (i12 == 10) {
                a(d, i);
            } else {
                a(i12, s);
            }
            iA++;
        }
        if (this.c < this.g.b) {
            this.d = this.c;
        }
        int i13 = this.c;
        this.c = i13 + 1;
        a(62, i13);
        this.g.H = z;
        c(d, i);
    }

    private void c(D d, int i, C c) {
        if (i + this.c < this.g.b) {
            this.d = this.c;
        }
        int i2 = this.c;
        this.c = i2 + 1;
        a(60, i2);
        int i3 = this.c;
        this.c = i3 + 1;
        a(63, i3);
        a(d, (short) 16, i, c.g, c.e, c.f);
        if (c.f <= 0 || c.g[c.f - 1] != 63) {
            int i4 = this.c;
            this.c = i4 + 1;
            a(63, i4);
        }
        int i5 = this.c;
        this.c = i5 + 1;
        a(62, i5);
        c(d, i);
    }

    private void d(D d, int i, C c) {
        if (i + this.c < this.g.b) {
            this.d = this.c;
        }
        int i2 = this.c;
        this.c = i2 + 1;
        a(60, i2);
        int i3 = this.c;
        this.c = i3 + 1;
        a(63, i3);
        int i4 = this.c;
        this.c = i4 + 1;
        a(120, i4);
        int i5 = this.c;
        this.c = i5 + 1;
        a(109, i5);
        int i6 = this.c;
        this.c = i6 + 1;
        a(108, i6);
        a(d, i, c, c.o);
        if (c.f <= 0 || c.g[c.f - 1] != 63) {
            int i7 = this.c;
            this.c = i7 + 1;
            a(63, i7);
        }
        int i8 = this.c;
        this.c = i8 + 1;
        a(62, i8);
        c(d, i);
    }

    private void e(D d, int i, C c) {
        int i2 = this.g.b;
        if (!this.g.N || !this.g.O) {
            this.g.b = 16777215;
        }
        int i3 = this.c;
        this.c = i3 + 1;
        a(60, i3);
        int i4 = this.c;
        this.c = i4 + 1;
        a(37, i4);
        a(d, this.g.N ? (short) 16 : (short) 2, i, c.g, c.e, c.f);
        int i5 = this.c;
        this.c = i5 + 1;
        a(37, i5);
        int i6 = this.c;
        this.c = i6 + 1;
        a(62, i6);
        this.g.b = i2;
    }

    private void f(D d, int i, C c) {
        int i2 = this.g.b;
        if (!this.g.O) {
            this.g.b = 16777215;
        }
        int i3 = this.c;
        this.c = i3 + 1;
        a(60, i3);
        int i4 = this.c;
        this.c = i4 + 1;
        a(35, i4);
        a(d, this.g.O ? (short) 16 : (short) 2, i, c.g, c.e, c.f);
        int i5 = this.c;
        this.c = i5 + 1;
        a(35, i5);
        int i6 = this.c;
        this.c = i6 + 1;
        a(62, i6);
        this.g.b = i2;
    }

    private void g(D d, int i, C c) {
        int i2 = this.g.b;
        if (!this.g.P) {
            this.g.b = 16777215;
        }
        int i3 = this.c;
        this.c = i3 + 1;
        a(60, i3);
        int i4 = this.c;
        this.c = i4 + 1;
        a(63, i4);
        a(d, this.g.P ? (short) 16 : (short) 2, i, c.g, c.e, c.f);
        int i5 = this.c;
        this.c = i5 + 1;
        a(63, i5);
        int i6 = this.c;
        this.c = i6 + 1;
        a(62, i6);
        this.g.b = i2;
    }

    private void h(D d, int i, C c) {
        int i2 = this.g.b;
        boolean z = this.g.af;
        c(d, 0);
        this.g.b = 16777215;
        int i3 = this.c;
        this.c = i3 + 1;
        a(60, i3);
        int i4 = this.c;
        this.c = i4 + 1;
        a(33, i4);
        int i5 = this.c;
        this.c = i5 + 1;
        a(91, i5);
        int i6 = this.c;
        this.c = i6 + 1;
        a(67, i6);
        int i7 = this.c;
        this.c = i7 + 1;
        a(68, i7);
        int i8 = this.c;
        this.c = i8 + 1;
        a(65, i8);
        int i9 = this.c;
        this.c = i9 + 1;
        a(84, i9);
        int i10 = this.c;
        this.c = i10 + 1;
        a(65, i10);
        int i11 = this.c;
        this.c = i11 + 1;
        a(91, i11);
        a(d, (short) 2, 0, c.g, c.e, c.f);
        int i12 = this.c;
        this.c = i12 + 1;
        a(93, i12);
        int i13 = this.c;
        this.c = i13 + 1;
        a(93, i13);
        int i14 = this.c;
        this.c = i14 + 1;
        a(62, i14);
        c(d, 0);
        this.g.b = i2;
    }

    private void i(D d, int i, C c) {
        int i2 = this.g.b;
        if (!this.g.M) {
            this.g.b = 16777215;
        }
        int i3 = this.c;
        this.c = i3 + 1;
        a(60, i3);
        int i4 = this.c;
        this.c = i4 + 1;
        a(33, i4);
        int i5 = this.c;
        this.c = i5 + 1;
        a(91, i5);
        a(d, this.g.M ? (short) 16 : (short) 2, i, c.g, c.e, c.f);
        int i6 = this.c;
        this.c = i6 + 1;
        a(93, i6);
        int i7 = this.c;
        this.c = i7 + 1;
        a(62, i7);
        this.g.b = i2;
    }

    private boolean c(C c) {
        while (c.m != this.g.ap.c) {
            if (c.a == null) {
                return false;
            }
            c = c.a;
            this = this;
        }
        return true;
    }

    private boolean d(C c) {
        Q q = this.g.ap;
        if (!this.g.m) {
            return false;
        }
        if (this.g.n) {
            if (c.p != null && S.a(c.m.c & 262144)) {
                C c2 = c.p;
                while (true) {
                    C c3 = c2;
                    if (c3 == null) {
                        return false;
                    }
                    if (c3.m != null && S.a(c3.m.c & 8)) {
                        return true;
                    }
                    c2 = c3.c;
                }
            } else if (S.a(c.m.c & 16384) || c.m == q.o || c.m == q.j) {
                return false;
            }
        }
        return S.a(c.m.c & 3072) || c.m == q.O || !S.a(c.m.c & 16);
    }

    public final void b(D d, short s, int i, B b, C c) {
        int i2;
        Q q = this.g.ap;
        if (c == null) {
            return;
        }
        if (c.h == 4 || (c.h == 8 && b.z.al)) {
            a(d, s, i, c.g, c.e, c.f);
            return;
        }
        if (c.h == 2) {
            c(d, i);
            a(d, 0, c);
            c(d, 0);
            return;
        }
        if (c.h != 0) {
            if (c.h == 1) {
                b(d, i, c);
                return;
            }
            if (c.h == 3) {
                c(d, i, c);
                return;
            }
            if (c.h == 13) {
                d(d, i, c);
                return;
            }
            if (c.h == 8) {
                h(d, i, c);
                return;
            }
            if (c.h == 9) {
                i(d, i, c);
                return;
            }
            if (c.h == 10) {
                e(d, i, c);
                return;
            }
            if (c.h == 11) {
                f(d, i, c);
                return;
            }
            if (c.h == 12) {
                g(d, i, c);
                return;
            }
            if (S.a(c.m.c & 1) || (c.h == 7 && !this.g.r)) {
                c(d, i);
                a(d, s, i, c);
                return;
            }
            boolean z = false;
            C c2 = c.p;
            while (true) {
                C c3 = c2;
                if (c3 == null) {
                    break;
                }
                if (c3.h == 4) {
                    z = true;
                    break;
                }
                c2 = c3.c;
            }
            c(d, i);
            if (J.a(c, q)) {
                i = 0;
                i2 = 0;
                z = false;
            } else {
                i2 = z ? i : i + this.g.a;
            }
            a(d, s, i, c);
            if (!z && c.p != null) {
                a(d, i);
            }
            C c4 = c.p;
            while (true) {
                C c5 = c4;
                if (c5 == null) {
                    break;
                }
                b(d, s, i2, b, c5);
                c4 = c5.c;
            }
            if (!z && c.p != null) {
                c(d, i2);
            }
            b(c);
            return;
        }
        C c6 = c.p;
        while (true) {
            C c7 = c6;
            if (c7 == null) {
                return;
            }
            b(d, s, i, b, c7);
            c6 = c7.c;
        }
    }

    private void a(D d, int i, C c, C0003b c0003b) {
        if (this.g.q) {
            boolean z = this.g.T;
        }
        if (c0003b != null) {
            if (c0003b.a != null) {
                a(d, i, c, c0003b.a);
            }
            if (c0003b.f == null) {
                if (c0003b.c != null) {
                    int i2 = this.c;
                    this.c = i2 + 1;
                    a(32, i2);
                    e(d, i, c0003b.c);
                    return;
                }
                if (c0003b.d != null) {
                    int i3 = this.c;
                    this.c = i3 + 1;
                    a(32, i3);
                    g(d, i, c0003b.d);
                    return;
                }
                return;
            }
            boolean z2 = this.g.A;
            boolean z3 = this.g.R;
            String str = c0003b.f;
            if (i + this.c >= this.g.b) {
                b(d, i);
            }
            if (!this.g.p && !this.g.q && c0003b.b != null) {
                if (C0007f.a().b(str)) {
                    boolean z4 = this.g.L;
                } else if (!c0003b.b.c()) {
                    boolean z5 = this.g.K;
                }
            }
            if (i + this.c < this.g.b) {
                this.d = this.c;
                int i4 = this.c;
                this.c = i4 + 1;
                a(32, i4);
            } else {
                c(d, i);
                int i5 = this.c;
                this.c = i5 + 1;
                a(32, i5);
            }
            for (int i6 = 0; i6 < str.length(); i6++) {
                char cA = S.a(str.charAt(i6), this.g.v, this.g.p);
                int i7 = this.c;
                this.c = i7 + 1;
                a(cA, i7);
            }
            if (i + this.c >= this.g.b) {
                b(d, i);
            }
            if (c0003b.g != null) {
                a(d, i, c0003b.g, c0003b.e, false);
                return;
            }
            if (this.g.p || this.g.q) {
                a(d, i, c0003b.a() ? c0003b.f : "", c0003b.e, true);
                return;
            }
            if (c0003b.a() || c == null || c.d()) {
                if (i + this.c < this.g.b) {
                    this.d = this.c;
                }
            } else {
                a(d, i, "", c0003b.e, true);
            }
        }
    }

    /* JADX WARN: Removed duplicated region for block: B:132:0x0375  */
    /* JADX WARN: Removed duplicated region for block: B:160:0x0468  */
    /*
        Code decompiled incorrectly, please refer to instructions dump.
        To view partially-correct code enable 'Show inconsistent code' option in preferences
    */
    public final void a(org.w3c.tidy.D r9, short r10, int r11, org.w3c.tidy.B r12, org.w3c.tidy.C r13) {
        /*
            Method dump skipped, instruction units count: 2136
            To view this dump change 'Code comments level' option to 'DEBUG'
        */
        throw new UnsupportedOperationException("Method not decompiled: org.w3c.tidy.F.a(org.w3c.tidy.D, short, int, org.w3c.tidy.B, org.w3c.tidy.C):void");
    }
}
