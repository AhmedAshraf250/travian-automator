package traviaut.xml;

import java.util.concurrent.TimeUnit;
import javax.xml.bind.annotation.XmlAttribute;

/* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TAVillage.class */
public class TAVillage {
    private static final long MAX_TIME = TimeUnit.DAYS.toMillis(50);

    @XmlAttribute
    public long lastused;
    public boolean celebrations;

    @XmlAttribute
    public long id = -1;

    @XmlAttribute
    public String username = "";
    public boolean work = true;
    public final TATrader trader = new TATrader();
    public final TATroops troops = new TATroops();
    public final TABuilder builder = new TABuilder();

    public void setParams(int i, String str) {
        this.id = i;
        this.username = str;
        this.lastused = System.currentTimeMillis();
    }

    public boolean isObsolete(long j) {
        return j - this.lastused > MAX_TIME;
    }

    public void clean(int i) {
        this.builder.clean(i);
    }
}
