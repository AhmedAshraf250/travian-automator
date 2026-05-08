package traviaut.xml;

import java.util.ArrayList;
import java.util.Iterator;
import java.util.List;
import javax.xml.bind.annotation.XmlAttribute;
import traviaut.f;

/* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TAServer.class */
public class TAServer {

    @XmlAttribute
    public String name;
    public List<TAVillage> village;

    public TAServer() {
        this.name = "";
        this.village = new ArrayList();
    }

    public TAServer(String str) {
        this.name = "";
        this.village = new ArrayList();
        this.name = str;
    }

    public void clean(int i) {
        long jCurrentTimeMillis = System.currentTimeMillis();
        Iterator<TAVillage> it = this.village.iterator();
        while (it.hasNext()) {
            TAVillage next = it.next();
            next.clean(i);
            if (next.isObsolete(jCurrentTimeMillis)) {
                it.remove();
            }
        }
    }

    private TAVillage findVill(int i, String str) {
        TAVillage tAVillage = null;
        Iterator<TAVillage> it = this.village.iterator();
        while (it.hasNext()) {
            TAVillage next = it.next();
            String str2 = next.username;
            if (i == next.id) {
                if (i == -1 && !str.equals(str2) && str2.length() != 0) {
                }
                return next;
            }
            if (next.id == -1) {
                if (str.equals(str2)) {
                    return next;
                }
                if (str2.length() == 0) {
                    tAVillage = next;
                }
            } else if (i == -1 && str.equals(str2)) {
                return next;
            }
        }
        return tAVillage;
    }

    public TAVillage getVillage(int i, String str) {
        TAVillage tAVillageFindVill = findVill(i, str);
        TAVillage tAVillage = tAVillageFindVill;
        if (tAVillageFindVill == null) {
            TAVillage tAVillage2 = new TAVillage();
            tAVillage = tAVillage2;
            tAVillage2.troops.settlers = f.c().settlersDefault;
            this.village.add(tAVillage);
        }
        tAVillage.setParams(i, str);
        return tAVillage;
    }
}
