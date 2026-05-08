package traviaut.xml;

import javax.xml.bind.annotation.XmlAttribute;

/* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TABuildPlace.class */
public class TABuildPlace {

    @XmlAttribute
    public boolean enabled;

    @XmlAttribute
    public int id;

    @XmlAttribute
    public int gid;

    @XmlAttribute
    public int maxlvl;

    @XmlAttribute
    public int priority;

    public TABuildPlace() {
        this.enabled = true;
        this.id = -1;
        this.maxlvl = -1;
        this.priority = 10;
    }

    public TABuildPlace(int i) {
        this.enabled = true;
        this.id = -1;
        this.maxlvl = -1;
        this.priority = 10;
        this.id = i;
    }

    public boolean isMaxLvl(int i) {
        return this.maxlvl != -1 && this.maxlvl <= i;
    }
}
