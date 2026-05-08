package org.a;

import java.util.LinkedHashMap;
import java.util.Map;
import traviaut.m;

/* JADX INFO: loaded from: traviaut.jar:org/a/c.class */
public final class c implements d {
    private final Map<String, d> b = new LinkedHashMap();
    public static final a a = new a();

    /* JADX INFO: loaded from: traviaut.jar:org/a/c$a.class */
    public static final class a implements d {
        protected final Object clone() {
            return this;
        }

        public final boolean equals(Object obj) {
            return obj == null || obj == this;
        }

        public final int hashCode() {
            return 7;
        }

        public final String toString() {
            return "null";
        }
    }

    /*  JADX ERROR: JadxRuntimeException in pass: RegionMakerVisitor
        jadx.core.utils.exceptions.JadxRuntimeException: Failed to find switch 'out' block (already processed)
        	at jadx.core.dex.visitors.regions.maker.SwitchRegionMaker.calcSwitchOut(SwitchRegionMaker.java:217)
        	at jadx.core.dex.visitors.regions.maker.SwitchRegionMaker.process(SwitchRegionMaker.java:68)
        	at jadx.core.dex.visitors.regions.maker.RegionMaker.traverse(RegionMaker.java:112)
        	at jadx.core.dex.visitors.regions.maker.RegionMaker.makeRegion(RegionMaker.java:66)
        	at jadx.core.dex.visitors.regions.maker.LoopRegionMaker.makeEndlessLoop(LoopRegionMaker.java:282)
        	at jadx.core.dex.visitors.regions.maker.LoopRegionMaker.process(LoopRegionMaker.java:65)
        	at jadx.core.dex.visitors.regions.maker.RegionMaker.traverse(RegionMaker.java:89)
        	at jadx.core.dex.visitors.regions.maker.RegionMaker.makeRegion(RegionMaker.java:66)
        	at jadx.core.dex.visitors.regions.maker.IfRegionMaker.process(IfRegionMaker.java:102)
        	at jadx.core.dex.visitors.regions.maker.RegionMaker.traverse(RegionMaker.java:106)
        	at jadx.core.dex.visitors.regions.maker.RegionMaker.makeRegion(RegionMaker.java:66)
        	at jadx.core.dex.visitors.regions.maker.RegionMaker.makeMthRegion(RegionMaker.java:48)
        	at jadx.core.dex.visitors.regions.RegionMakerVisitor.visit(RegionMakerVisitor.java:25)
        */
    public c(org.a.f r7) throws org.a.b {
        /*
            Method dump skipped, instruction units count: 245
            To view this dump change 'Code comments level' option to 'DEBUG'
        */
        throw new UnsupportedOperationException("Method not decompiled: org.a.c.<init>(org.a.f):void");
    }

    public final Object a(String str) {
        return this.b.get(str);
    }

    public final c b(String str) {
        Object objA = a(str);
        if (objA instanceof c) {
            return (c) objA;
        }
        return null;
    }

    public final String c(String str) {
        Object objA = a(str);
        return objA instanceof e ? ((e) objA).a : "";
    }

    public final int d(String str) {
        return m.c(((e) a(str)).a);
    }
}
