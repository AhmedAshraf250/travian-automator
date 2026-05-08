package traviaut.xml;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Map;

/* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TAServList.class */
public class TAServList {
    private final Map<String, TAServer> map = new HashMap();
    public final List<TAServer> server = new ArrayList();

    public void clean(int i) {
        Iterator<TAServer> it = this.server.iterator();
        while (it.hasNext()) {
            TAServer next = it.next();
            next.clean(i);
            if (next.village.isEmpty()) {
                it.remove();
            } else {
                this.map.put(next.name, next);
            }
        }
    }

    public TAServer getServer(String str) {
        TAServer tAServer = this.map.get(str);
        TAServer tAServer2 = tAServer;
        if (tAServer == null) {
            tAServer2 = new TAServer(str);
            this.map.put(str, tAServer2);
            this.server.add(tAServer2);
        }
        return tAServer2;
    }
}
