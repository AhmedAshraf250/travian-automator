package traviaut.xml;

import javax.xml.bind.annotation.XmlAttribute;

/* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TAHeroItem.class */
public class TAHeroItem {

    @XmlAttribute
    public int id;

    @XmlAttribute
    public boolean sell;

    @XmlAttribute
    public int reserve;

    public TAHeroItem() {
    }

    public TAHeroItem(int i) {
        this.id = i;
    }
}
