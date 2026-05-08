package traviaut;

import java.util.Iterator;
import java.util.concurrent.BlockingQueue;
import java.util.concurrent.LinkedBlockingQueue;
import java.util.concurrent.TimeUnit;
import traviaut.b.r;

/* JADX INFO: loaded from: traviaut.jar:traviaut/k.class */
public final class k implements Runnable {
    private static final k a = new k();
    private static final long b = TimeUnit.MINUTES.toMillis(1);
    private final BlockingQueue<a> c = new LinkedBlockingQueue();
    private traviaut.gui.e d;
    private boolean e;

    /* JADX INFO: loaded from: traviaut.jar:traviaut/k$a.class */
    public static class a {
        public final traviaut.a.k a;
        public final r b;
        public final e c;
        public final long d;

        public a() {
            this.b = null;
            this.a = null;
            this.c = null;
            this.d = Long.MAX_VALUE;
        }

        public a(e eVar, long j) {
            this.a = eVar;
            this.b = null;
            this.c = eVar;
            this.d = j;
        }

        public a(r rVar, long j) {
            this.a = rVar;
            this.b = rVar;
            this.c = this.b.d;
            this.d = j;
        }

        public a(traviaut.a.k kVar, e eVar, long j) {
            this.a = kVar;
            this.b = null;
            this.c = eVar;
            this.d = j;
        }

        private a(traviaut.a.k kVar, r rVar) {
            this.a = kVar;
            this.b = rVar;
            this.c = this.b.d;
            this.d = 0L;
        }

        /* synthetic */ a(traviaut.a.k kVar, r rVar, byte b) {
            this(kVar, rVar);
        }
    }

    public static k a() {
        return a;
    }

    public final synchronized void b(e eVar) {
        if (this.d != null) {
            this.d.a(eVar);
        }
    }

    public final synchronized void a(traviaut.gui.e eVar) {
        this.d = eVar;
        if (eVar == null) {
            this.e = true;
            this.c.offer(new a());
        }
    }

    @Override // java.lang.Runnable
    public final void run() {
        long jMin;
        a aVarPoll;
        while (!this.e) {
            a aVar = null;
            try {
                try {
                    try {
                        try {
                            if (this.c.isEmpty()) {
                                long jCurrentTimeMillis = System.currentTimeMillis();
                                c cVar = Main.a;
                                a aVar2 = new a();
                                Iterator<e> it = cVar.a.iterator();
                                while (it.hasNext()) {
                                    a aVarA = it.next().a(jCurrentTimeMillis, aVar2);
                                    aVar2 = aVarA;
                                    if (aVarA.d == 1) {
                                        break;
                                    }
                                }
                                a aVar3 = aVar2;
                                if (aVar3.d <= jCurrentTimeMillis && aVar3.a != null) {
                                    this.c.offer(aVar3);
                                }
                                jMin = Math.min(aVar3.d - jCurrentTimeMillis, b);
                            } else {
                                jMin = b;
                            }
                            aVarPoll = this.c.poll(jMin, TimeUnit.MILLISECONDS);
                        } catch (InterruptedException e) {
                            g.a("getParser interrupted", e);
                            Thread.currentThread().interrupt();
                            if (0 != 0 && aVar.c != null) {
                                b(aVar.c);
                            }
                        }
                    } catch (traviaut.b.i e2) {
                        if (aVar.c != null) {
                            String str = "exception: " + e2.getMessage();
                            if (e2.getCause() != null) {
                                str = str + " cause: " + e2.getCause().getMessage();
                            }
                            aVar.c.a(str);
                            e2.a(aVar.c.d);
                        }
                        if (0 != 0 && aVar.c != null) {
                            b(aVar.c);
                        }
                    }
                } catch (Exception e3) {
                    if (0 != 0 && aVar.c != null) {
                        aVar.c.a("exception: " + e3.getMessage());
                        aVar.c.d.a(0, e3.getMessage());
                    }
                    g.a("exception in requester, please contact author", e3);
                    try {
                        Thread.sleep(TimeUnit.SECONDS.toMillis(1L));
                    } catch (InterruptedException e4) {
                        g.a("getParser interrupted", e4);
                        Thread.currentThread().interrupt();
                    }
                    if (0 != 0 && aVar.c != null) {
                        b(aVar.c);
                    }
                }
                if (aVarPoll != null && aVarPoll.a != null) {
                    aVarPoll.a.a(aVarPoll);
                    org.a.f.a("check 20161021.0-tap", null);
                    if (aVarPoll != null && aVarPoll.c != null) {
                        b(aVarPoll.c);
                    }
                } else if (aVarPoll != null && aVarPoll.c != null) {
                    b(aVarPoll.c);
                }
            } catch (Throwable th) {
                if (0 != 0 && aVar.c != null) {
                    b(aVar.c);
                }
                throw th;
            }
        }
    }

    public static void a(e eVar) {
        a.c.offer(new a(eVar, 1L));
    }

    public static void a(r rVar) {
        a.c.offer(new a(rVar, 1L));
    }

    public static void a(traviaut.a.k kVar, r rVar) {
        a.c.offer(new a(kVar, rVar, (byte) 0));
    }

    public static void a(traviaut.b.n nVar, String str) {
        a.c.offer(new a(nVar, new e(str, "dummy", "dummy"), 0L));
    }
}
