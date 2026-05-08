package traviaut.a;

import java.util.Random;
import java.util.function.Function;
import org.w3c.dom.Element;
import traviaut.Main;
import traviaut.k;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/s.class */
public final class s extends u {
    private static Random b = new Random();
    private int c;

    private static boolean b(traviaut.e eVar) {
        return traviaut.f.c().readreports && eVar.e.k > 0;
    }

    private static boolean c(traviaut.e eVar) {
        return traviaut.f.c().readmessages && eVar.e.l > 0;
    }

    @Override // traviaut.a.u
    public final boolean a(traviaut.e eVar) {
        if (!Main.b()) {
            return false;
        }
        if (!b(eVar) && !c(eVar)) {
            return false;
        }
        int i = this.c - 1;
        this.c = i;
        return i < 0;
    }

    @Override // traviaut.a.u
    public final long b(k.a aVar) throws traviaut.b.i {
        traviaut.e eVar = aVar.c;
        if (b(eVar)) {
            a(eVar, "berichte.php", "reports", eVar.e.k, eVar2 -> {
                return eVar2.a("td", "class", "newMessage").a("div", 0).a("a").c("href");
            });
        }
        if (c(eVar)) {
            a(eVar, "nachrichten.php", "messages", eVar.e.l, eVar3 -> {
                return eVar3.a("img", "class", "messageStatusUnread").a().a().a("a", 1).c("href");
            });
        }
        this.c = b.nextInt(100);
        return 1L;
    }

    private static void a(traviaut.e eVar, String str, String str2, int i, Function<traviaut.b.e, String> function) throws traviaut.b.i {
        eVar.a("reading " + str2);
        int i2 = 0;
        int iMin = Math.min(i, 5);
        int i3 = 0;
        while (i2 < iMin && i3 < 10) {
            Element elementA = eVar.b(str).a("div", "id", "content");
            String strApply = function.apply(new traviaut.b.e(elementA));
            if (strApply == null) {
                String strC = new traviaut.b.e(elementA).a("div", "class", "paginator").a("a", "class", "next").c("href");
                str = strC;
                if (strC == null) {
                    break;
                } else {
                    i3++;
                }
            } else {
                i2++;
                eVar.b(strApply);
            }
        }
        if (i2 < iMin) {
            eVar.a("some " + str2 + " are too far, read them manually please");
        }
        eVar.a("done: " + i2);
    }
}
