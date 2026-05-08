package traviaut.a;

import traviaut.xml.TAMerchant;

/* JADX INFO: loaded from: traviaut.jar:traviaut/a/t.class */
public final class t {
    public int a;
    public int b;
    public int[] c;

    public t(traviaut.b.l lVar, TAMerchant tAMerchant) {
        lVar.d();
        this.c = lVar.e();
        for (int i = 1; i < 4; i++) {
            int i2 = this.c[i];
            if (i2 > this.c[this.b]) {
                this.b = i;
            }
            if (i2 < this.c[this.a]) {
                this.a = i;
            }
        }
        if (tAMerchant.isPreffered()) {
            if (tAMerchant.prefsell != TAMerchant.ResTypes.NONE) {
                this.b = tAMerchant.prefsell.ordinal();
            }
            if (tAMerchant.prefbuy != TAMerchant.ResTypes.NONE) {
                this.a = tAMerchant.prefbuy.ordinal();
            }
        }
    }
}
