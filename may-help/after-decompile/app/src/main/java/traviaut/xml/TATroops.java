package traviaut.xml;

import javax.xml.bind.annotation.XmlAttribute;

/* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TATroops.class */
public class TATroops {

    @XmlAttribute
    public boolean enabled;
    public boolean[] types = new boolean[8];
    public boolean settlers;

    public boolean isEnabled(int i) {
        return this.enabled && this.types[i];
    }
}
