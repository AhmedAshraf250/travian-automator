package traviaut.xml;

/* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TAMerchant.class */
public class TAMerchant {
    public boolean enabled;
    public int period = 15;
    public int resbalancefactor = 300;
    public int merchants = 3;
    public int merchantreserve = 5;
    public int marketlimit = 10;
    public int officelimit = 0;
    public ResTypes prefsell = ResTypes.NONE;
    public ResTypes prefbuy = ResTypes.NONE;
    public boolean sellcrop = true;

    /* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TAMerchant$ResTypes.class */
    public enum ResTypes {
        LUMBER,
        CLAY,
        IRON,
        CROP,
        NONE
    }

    public boolean isPreffered() {
        return this.prefbuy != this.prefsell;
    }
}
