package traviaut.xml;

import javax.xml.bind.annotation.XmlAttribute;
import javax.xml.bind.annotation.XmlElement;
import javax.xml.bind.annotation.XmlRootElement;

/* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TAData.class */
@XmlRootElement(name = "data")
public class TAData {
    public static final int ACT_VER = 4;

    @XmlAttribute
    public int version;
    public final TAGui gui = new TAGui();

    @XmlElement(name = "global")
    private final TAGlobalSets globalSets = new TAGlobalSets();
    public final TAAccounts accounts = new TAAccounts();
    public final TAServList servers = new TAServList();

    public void clean() {
        int i = this.version;
        this.version = 4;
        this.globalSets.hero.fillMap();
        this.accounts.postLoad();
        this.servers.clean(i);
    }

    public TAGlobalSets getSets() {
        return this.globalSets;
    }
}
