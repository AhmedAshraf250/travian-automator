package traviaut.gui.properties;

import traviaut.xml.TAMerchant;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/q.class */
public final class q extends w {
    public q(Object obj) {
        super(obj);
        this.a.add(new s("enabled", "enable merchant", ""));
        this.a.add(new v("period", "period in minutes", "how often should TA try to make the offer", 1, 500, 1));
        this.a.add(new v("resbalancefactor", "resource balancing factor in %", "default 200% means, that TA will make the offer when some resource is higher by 200% than some other resource", 100, 1000, 10));
        this.a.add(new v("merchants", "merchants to send", "merchants used for the offer", 1, 20, 1));
        this.a.add(new v("merchantreserve", "merchants to keep", "how many merchants should stay at home", 0, 20, 1));
        this.a.add(new v("marketlimit", "minimal marketplace level", "do not make the offer when the marketplace is not upgraded at least to this level", 0, 20, 1));
        this.a.add(new v("officelimit", "minimal trade office level", "do not make the offer when the trade office is not upgraded at least to this level", 0, 20, 1));
        this.a.add(new t("prefsell", "Prefered resource to sell: ", TAMerchant.ResTypes.values()));
        this.a.add(new t("prefbuy", "Prefered resource to buy: ", TAMerchant.ResTypes.values()));
        this.a.add(new s("sellcrop", "offer crop", ""));
    }

    @Override // traviaut.gui.properties.u
    public final String a() {
        return "Merchant";
    }
}
