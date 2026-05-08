package org.w3c.tidy;

import java.io.UnsupportedEncodingException;

/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/S.class */
public final class S {
    private static short[] a = new short[128];

    static boolean a(int i) {
        return i != 0;
    }

    static int b(int i) {
        return i & 255;
    }

    static boolean a(String[] strArr, String str) {
        for (String str2 : strArr) {
            if (str2.equalsIgnoreCase(str)) {
                return true;
            }
        }
        return false;
    }

    public static boolean a(String str, String str2, int i) {
        int length = str.length();
        int i2 = 0;
        while (length < i) {
            String strSubstring = str2.substring(i2, i2 + length);
            if (str.equalsIgnoreCase(strSubstring)) {
                return !strSubstring.equals(str.substring(0, length));
            }
            i2++;
            i--;
        }
        return false;
    }

    static boolean a(char c) {
        if (c >= 'A' && c <= 'Z') {
            return true;
        }
        if (c >= 'a' && c <= 'z') {
            return true;
        }
        if (c >= 192 && c <= 214) {
            return true;
        }
        if (c >= 216 && c <= 246) {
            return true;
        }
        if (c >= 248 && c <= 255) {
            return true;
        }
        if (c >= 256 && c <= 305) {
            return true;
        }
        if (c >= 308 && c <= 318) {
            return true;
        }
        if (c >= 321 && c <= 328) {
            return true;
        }
        if (c >= 330 && c <= 382) {
            return true;
        }
        if (c >= 384 && c <= 451) {
            return true;
        }
        if (c >= 461 && c <= 496) {
            return true;
        }
        if (c >= 500 && c <= 501) {
            return true;
        }
        if (c >= 506 && c <= 535) {
            return true;
        }
        if (c >= 592 && c <= 680) {
            return true;
        }
        if ((c >= 699 && c <= 705) || c == 902) {
            return true;
        }
        if ((c >= 904 && c <= 906) || c == 908) {
            return true;
        }
        if (c >= 910 && c <= 929) {
            return true;
        }
        if (c >= 931 && c <= 974) {
            return true;
        }
        if ((c >= 976 && c <= 982) || c == 986 || c == 988 || c == 990 || c == 992) {
            return true;
        }
        if (c >= 994 && c <= 1011) {
            return true;
        }
        if (c >= 1025 && c <= 1036) {
            return true;
        }
        if (c >= 1038 && c <= 1103) {
            return true;
        }
        if (c >= 1105 && c <= 1116) {
            return true;
        }
        if (c >= 1118 && c <= 1153) {
            return true;
        }
        if (c >= 1168 && c <= 1220) {
            return true;
        }
        if (c >= 1223 && c <= 1224) {
            return true;
        }
        if (c >= 1227 && c <= 1228) {
            return true;
        }
        if (c >= 1232 && c <= 1259) {
            return true;
        }
        if (c >= 1262 && c <= 1269) {
            return true;
        }
        if (c >= 1272 && c <= 1273) {
            return true;
        }
        if ((c >= 1329 && c <= 1366) || c == 1369) {
            return true;
        }
        if (c >= 1377 && c <= 1414) {
            return true;
        }
        if (c >= 1488 && c <= 1514) {
            return true;
        }
        if (c >= 1520 && c <= 1522) {
            return true;
        }
        if (c >= 1569 && c <= 1594) {
            return true;
        }
        if (c >= 1601 && c <= 1610) {
            return true;
        }
        if (c >= 1649 && c <= 1719) {
            return true;
        }
        if (c >= 1722 && c <= 1726) {
            return true;
        }
        if (c >= 1728 && c <= 1742) {
            return true;
        }
        if ((c >= 1744 && c <= 1747) || c == 1749) {
            return true;
        }
        if (c >= 1765 && c <= 1766) {
            return true;
        }
        if ((c >= 2309 && c <= 2361) || c == 2365) {
            return true;
        }
        if (c >= 2392 && c <= 2401) {
            return true;
        }
        if (c >= 2437 && c <= 2444) {
            return true;
        }
        if (c >= 2447 && c <= 2448) {
            return true;
        }
        if (c >= 2451 && c <= 2472) {
            return true;
        }
        if ((c >= 2474 && c <= 2480) || c == 2482) {
            return true;
        }
        if (c >= 2486 && c <= 2489) {
            return true;
        }
        if (c >= 2524 && c <= 2525) {
            return true;
        }
        if (c >= 2527 && c <= 2529) {
            return true;
        }
        if (c >= 2544 && c <= 2545) {
            return true;
        }
        if (c >= 2565 && c <= 2570) {
            return true;
        }
        if (c >= 2575 && c <= 2576) {
            return true;
        }
        if (c >= 2579 && c <= 2600) {
            return true;
        }
        if (c >= 2602 && c <= 2608) {
            return true;
        }
        if (c >= 2610 && c <= 2611) {
            return true;
        }
        if (c >= 2613 && c <= 2614) {
            return true;
        }
        if (c >= 2616 && c <= 2617) {
            return true;
        }
        if ((c >= 2649 && c <= 2652) || c == 2654) {
            return true;
        }
        if (c >= 2674 && c <= 2676) {
            return true;
        }
        if ((c >= 2693 && c <= 2699) || c == 2701) {
            return true;
        }
        if (c >= 2703 && c <= 2705) {
            return true;
        }
        if (c >= 2707 && c <= 2728) {
            return true;
        }
        if (c >= 2730 && c <= 2736) {
            return true;
        }
        if (c >= 2738 && c <= 2739) {
            return true;
        }
        if ((c >= 2741 && c <= 2745) || c == 2749 || c == 2784) {
            return true;
        }
        if (c >= 2821 && c <= 2828) {
            return true;
        }
        if (c >= 2831 && c <= 2832) {
            return true;
        }
        if (c >= 2835 && c <= 2856) {
            return true;
        }
        if (c >= 2858 && c <= 2864) {
            return true;
        }
        if (c >= 2866 && c <= 2867) {
            return true;
        }
        if ((c >= 2870 && c <= 2873) || c == 2877) {
            return true;
        }
        if (c >= 2908 && c <= 2909) {
            return true;
        }
        if (c >= 2911 && c <= 2913) {
            return true;
        }
        if (c >= 2949 && c <= 2954) {
            return true;
        }
        if (c >= 2958 && c <= 2960) {
            return true;
        }
        if (c >= 2962 && c <= 2965) {
            return true;
        }
        if ((c >= 2969 && c <= 2970) || c == 2972) {
            return true;
        }
        if (c >= 2974 && c <= 2975) {
            return true;
        }
        if (c >= 2979 && c <= 2980) {
            return true;
        }
        if (c >= 2984 && c <= 2986) {
            return true;
        }
        if (c >= 2990 && c <= 2997) {
            return true;
        }
        if (c >= 2999 && c <= 3001) {
            return true;
        }
        if (c >= 3077 && c <= 3084) {
            return true;
        }
        if (c >= 3086 && c <= 3088) {
            return true;
        }
        if (c >= 3090 && c <= 3112) {
            return true;
        }
        if (c >= 3114 && c <= 3123) {
            return true;
        }
        if (c >= 3125 && c <= 3129) {
            return true;
        }
        if (c >= 3168 && c <= 3169) {
            return true;
        }
        if (c >= 3205 && c <= 3212) {
            return true;
        }
        if (c >= 3214 && c <= 3216) {
            return true;
        }
        if (c >= 3218 && c <= 3240) {
            return true;
        }
        if (c >= 3242 && c <= 3251) {
            return true;
        }
        if ((c >= 3253 && c <= 3257) || c == 3294) {
            return true;
        }
        if (c >= 3296 && c <= 3297) {
            return true;
        }
        if (c >= 3333 && c <= 3340) {
            return true;
        }
        if (c >= 3342 && c <= 3344) {
            return true;
        }
        if (c >= 3346 && c <= 3368) {
            return true;
        }
        if (c >= 3370 && c <= 3385) {
            return true;
        }
        if (c >= 3424 && c <= 3425) {
            return true;
        }
        if ((c >= 3585 && c <= 3630) || c == 3632) {
            return true;
        }
        if (c >= 3634 && c <= 3635) {
            return true;
        }
        if (c >= 3648 && c <= 3653) {
            return true;
        }
        if ((c >= 3713 && c <= 3714) || c == 3716) {
            return true;
        }
        if ((c >= 3719 && c <= 3720) || c == 3722 || c == 3725) {
            return true;
        }
        if (c >= 3732 && c <= 3735) {
            return true;
        }
        if (c >= 3737 && c <= 3743) {
            return true;
        }
        if ((c >= 3745 && c <= 3747) || c == 3749 || c == 3751) {
            return true;
        }
        if (c >= 3754 && c <= 3755) {
            return true;
        }
        if ((c >= 3757 && c <= 3758) || c == 3760) {
            return true;
        }
        if ((c >= 3762 && c <= 3763) || c == 3773) {
            return true;
        }
        if (c >= 3776 && c <= 3780) {
            return true;
        }
        if (c >= 3904 && c <= 3911) {
            return true;
        }
        if (c >= 3913 && c <= 3945) {
            return true;
        }
        if (c >= 4256 && c <= 4293) {
            return true;
        }
        if ((c >= 4304 && c <= 4342) || c == 4352) {
            return true;
        }
        if (c >= 4354 && c <= 4355) {
            return true;
        }
        if ((c >= 4357 && c <= 4359) || c == 4361) {
            return true;
        }
        if (c >= 4363 && c <= 4364) {
            return true;
        }
        if ((c >= 4366 && c <= 4370) || c == 4412 || c == 4414 || c == 4416 || c == 4428 || c == 4430 || c == 4432) {
            return true;
        }
        if ((c >= 4436 && c <= 4437) || c == 4441) {
            return true;
        }
        if ((c >= 4447 && c <= 4449) || c == 4451 || c == 4453 || c == 4455 || c == 4457) {
            return true;
        }
        if (c >= 4461 && c <= 4462) {
            return true;
        }
        if ((c >= 4466 && c <= 4467) || c == 4469 || c == 4510 || c == 4520 || c == 4523) {
            return true;
        }
        if (c >= 4526 && c <= 4527) {
            return true;
        }
        if ((c >= 4535 && c <= 4536) || c == 4538) {
            return true;
        }
        if ((c >= 4540 && c <= 4546) || c == 4587 || c == 4592 || c == 4601) {
            return true;
        }
        if (c >= 7680 && c <= 7835) {
            return true;
        }
        if (c >= 7840 && c <= 7929) {
            return true;
        }
        if (c >= 7936 && c <= 7957) {
            return true;
        }
        if (c >= 7960 && c <= 7965) {
            return true;
        }
        if (c >= 7968 && c <= 8005) {
            return true;
        }
        if (c >= 8008 && c <= 8013) {
            return true;
        }
        if ((c >= 8016 && c <= 8023) || c == 8025 || c == 8027 || c == 8029) {
            return true;
        }
        if (c >= 8031 && c <= 8061) {
            return true;
        }
        if (c >= 8064 && c <= 8116) {
            return true;
        }
        if ((c >= 8118 && c <= 8124) || c == 8126) {
            return true;
        }
        if (c >= 8130 && c <= 8132) {
            return true;
        }
        if (c >= 8134 && c <= 8140) {
            return true;
        }
        if (c >= 8144 && c <= 8147) {
            return true;
        }
        if (c >= 8150 && c <= 8155) {
            return true;
        }
        if (c >= 8160 && c <= 8172) {
            return true;
        }
        if (c >= 8178 && c <= 8180) {
            return true;
        }
        if ((c >= 8182 && c <= 8188) || c == 8486) {
            return true;
        }
        if ((c >= 8490 && c <= 8491) || c == 8494) {
            return true;
        }
        if (c >= 8576 && c <= 8578) {
            return true;
        }
        if (c >= 12353 && c <= 12436) {
            return true;
        }
        if (c >= 12449 && c <= 12538) {
            return true;
        }
        if (c >= 12549 && c <= 12588) {
            return true;
        }
        if (c >= 44032 && c <= 55203) {
            return true;
        }
        if ((c >= 19968 && c <= 40869) || c == 12295) {
            return true;
        }
        if (c >= 12321 && c <= 12329) {
            return true;
        }
        if ((c < 19968 || c > 40869) && c != 12295) {
            return c >= 12321 && c <= 12329;
        }
        return true;
    }

    static boolean b(char c) {
        if (a(c) || c == '.' || c == '_' || c == ':' || c == '-') {
            return true;
        }
        if (c >= 768 && c <= 837) {
            return true;
        }
        if (c >= 864 && c <= 865) {
            return true;
        }
        if (c >= 1155 && c <= 1158) {
            return true;
        }
        if (c >= 1425 && c <= 1441) {
            return true;
        }
        if (c >= 1443 && c <= 1465) {
            return true;
        }
        if ((c >= 1467 && c <= 1469) || c == 1471) {
            return true;
        }
        if ((c >= 1473 && c <= 1474) || c == 1476) {
            return true;
        }
        if ((c >= 1611 && c <= 1618) || c == 1648) {
            return true;
        }
        if (c >= 1750 && c <= 1756) {
            return true;
        }
        if (c >= 1757 && c <= 1759) {
            return true;
        }
        if (c >= 1760 && c <= 1764) {
            return true;
        }
        if (c >= 1767 && c <= 1768) {
            return true;
        }
        if (c >= 1770 && c <= 1773) {
            return true;
        }
        if ((c >= 2305 && c <= 2307) || c == 2364) {
            return true;
        }
        if ((c >= 2366 && c <= 2380) || c == 2381) {
            return true;
        }
        if (c >= 2385 && c <= 2388) {
            return true;
        }
        if (c >= 2402 && c <= 2403) {
            return true;
        }
        if ((c >= 2433 && c <= 2435) || c == 2492 || c == 2494 || c == 2495) {
            return true;
        }
        if (c >= 2496 && c <= 2500) {
            return true;
        }
        if (c >= 2503 && c <= 2504) {
            return true;
        }
        if ((c >= 2507 && c <= 2509) || c == 2519) {
            return true;
        }
        if ((c >= 2530 && c <= 2531) || c == 2562 || c == 2620 || c == 2622 || c == 2623) {
            return true;
        }
        if (c >= 2624 && c <= 2626) {
            return true;
        }
        if (c >= 2631 && c <= 2632) {
            return true;
        }
        if (c >= 2635 && c <= 2637) {
            return true;
        }
        if (c >= 2672 && c <= 2673) {
            return true;
        }
        if ((c >= 2689 && c <= 2691) || c == 2748) {
            return true;
        }
        if (c >= 2750 && c <= 2757) {
            return true;
        }
        if (c >= 2759 && c <= 2761) {
            return true;
        }
        if (c >= 2763 && c <= 2765) {
            return true;
        }
        if ((c >= 2817 && c <= 2819) || c == 2876) {
            return true;
        }
        if (c >= 2878 && c <= 2883) {
            return true;
        }
        if (c >= 2887 && c <= 2888) {
            return true;
        }
        if (c >= 2891 && c <= 2893) {
            return true;
        }
        if (c >= 2902 && c <= 2903) {
            return true;
        }
        if (c >= 2946 && c <= 2947) {
            return true;
        }
        if (c >= 3006 && c <= 3010) {
            return true;
        }
        if (c >= 3014 && c <= 3016) {
            return true;
        }
        if ((c >= 3018 && c <= 3021) || c == 3031) {
            return true;
        }
        if (c >= 3073 && c <= 3075) {
            return true;
        }
        if (c >= 3134 && c <= 3140) {
            return true;
        }
        if (c >= 3142 && c <= 3144) {
            return true;
        }
        if (c >= 3146 && c <= 3149) {
            return true;
        }
        if (c >= 3157 && c <= 3158) {
            return true;
        }
        if (c >= 3202 && c <= 3203) {
            return true;
        }
        if (c >= 3262 && c <= 3268) {
            return true;
        }
        if (c >= 3270 && c <= 3272) {
            return true;
        }
        if (c >= 3274 && c <= 3277) {
            return true;
        }
        if (c >= 3285 && c <= 3286) {
            return true;
        }
        if (c >= 3330 && c <= 3331) {
            return true;
        }
        if (c >= 3390 && c <= 3395) {
            return true;
        }
        if (c >= 3398 && c <= 3400) {
            return true;
        }
        if ((c >= 3402 && c <= 3405) || c == 3415 || c == 3633) {
            return true;
        }
        if (c >= 3636 && c <= 3642) {
            return true;
        }
        if ((c >= 3655 && c <= 3662) || c == 3761) {
            return true;
        }
        if (c >= 3764 && c <= 3769) {
            return true;
        }
        if (c >= 3771 && c <= 3772) {
            return true;
        }
        if (c >= 3784 && c <= 3789) {
            return true;
        }
        if ((c >= 3864 && c <= 3865) || c == 3893 || c == 3895 || c == 3897 || c == 3902 || c == 3903) {
            return true;
        }
        if (c >= 3953 && c <= 3972) {
            return true;
        }
        if (c >= 3974 && c <= 3979) {
            return true;
        }
        if ((c >= 3984 && c <= 3989) || c == 3991) {
            return true;
        }
        if (c >= 3993 && c <= 4013) {
            return true;
        }
        if ((c >= 4017 && c <= 4023) || c == 4025) {
            return true;
        }
        if ((c >= 8400 && c <= 8412) || c == 8417) {
            return true;
        }
        if ((c >= 12330 && c <= 12335) || c == 12441 || c == 12442) {
            return true;
        }
        if (c >= '0' && c <= '9') {
            return true;
        }
        if (c >= 1632 && c <= 1641) {
            return true;
        }
        if (c >= 1776 && c <= 1785) {
            return true;
        }
        if (c >= 2406 && c <= 2415) {
            return true;
        }
        if (c >= 2534 && c <= 2543) {
            return true;
        }
        if (c >= 2662 && c <= 2671) {
            return true;
        }
        if (c >= 2790 && c <= 2799) {
            return true;
        }
        if (c >= 2918 && c <= 2927) {
            return true;
        }
        if (c >= 3047 && c <= 3055) {
            return true;
        }
        if (c >= 3174 && c <= 3183) {
            return true;
        }
        if (c >= 3302 && c <= 3311) {
            return true;
        }
        if (c >= 3430 && c <= 3439) {
            return true;
        }
        if (c >= 3664 && c <= 3673) {
            return true;
        }
        if (c >= 3792 && c <= 3801) {
            return true;
        }
        if ((c >= 3872 && c <= 3881) || c == 183 || c == 720 || c == 721 || c == 903 || c == 1600 || c == 3654 || c == 3782 || c == 12293) {
            return true;
        }
        if (c >= 12337 && c <= 12341) {
            return true;
        }
        if (c < 12445 || c > 12446) {
            return c >= 12540 && c <= 12542;
        }
        return true;
    }

    static boolean c(int i) {
        return i == 39 || i == 34;
    }

    public static byte[] a(String str) {
        try {
            return str.getBytes("UTF8");
        } catch (UnsupportedEncodingException e) {
            throw new Error("String to UTF-8 conversion failed: " + e.getMessage());
        }
    }

    public static String a(byte[] bArr, int i, int i2) {
        try {
            return new String(bArr, i, i2, "UTF8");
        } catch (UnsupportedEncodingException e) {
            throw new Error("UTF-8 to string conversion failed: " + e.getMessage());
        }
    }

    public static int b(String str) {
        if (str == null || str.length() <= 0) {
            return 0;
        }
        return str.charAt(str.length() - 1);
    }

    public static boolean c(char c) {
        return a(i(c) & 8);
    }

    public static boolean d(char c) {
        return a(i(c) & 1);
    }

    public static boolean e(char c) {
        return a(i(c) & 2);
    }

    public static boolean f(char c) {
        return a(i(c) & 4);
    }

    public static boolean g(char c) {
        return a(i(c) & 64);
    }

    public static char h(char c) {
        if (a(i(c) & 64)) {
            c = (char) ((c + 'a') - 65);
        }
        return c;
    }

    private static void a(String str, short s) {
        for (int i = 0; i < str.length(); i++) {
            char cCharAt = str.charAt(i);
            short[] sArr = a;
            sArr[cCharAt] = (short) (sArr[cCharAt] | s);
        }
    }

    private static short i(char c) {
        if (c < 128) {
            return a[c];
        }
        return (short) 0;
    }

    public static boolean c(String str) {
        return w.a(str) != null;
    }

    static {
        a("\r\n\f", (short) 24);
        a(" \t", (short) 8);
        a("-.:_", (short) 4);
        a("0123456789", (short) 5);
        a("abcdefghijklmnopqrstuvwxyz", (short) 38);
        a("ABCDEFGHIJKLMNOPQRSTUVWXYZ", (short) 70);
    }

    public static char a(char c, boolean z, boolean z2) {
        if (!z2) {
            if (z) {
                if (a(i(c) & 32)) {
                    char c2 = c;
                    if (a(i(c) & 32)) {
                        c2 = (char) ((c2 + 'A') - 97);
                    }
                    c = c2;
                }
            } else if (g(c)) {
                c = h(c);
            }
        }
        return c;
    }
}
