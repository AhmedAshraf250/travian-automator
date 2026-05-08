package traviaut.xml;

import javax.xml.bind.annotation.XmlAttribute;

/* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TATrader.class */
public class TATrader {
    public int autotrade;
    public int autoreservemerch;

    @XmlAttribute
    public long arrival;
    public boolean tocrop = true;
    public boolean tobuild = true;
    public boolean upload = true;
    public int automaxmerch = 1;
    public int period = 30;

    public boolean supplyBuild() {
        return this.tobuild && this.autotrade == 0;
    }
}
