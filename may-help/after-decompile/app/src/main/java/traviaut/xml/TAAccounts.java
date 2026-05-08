package traviaut.xml;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

/* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TAAccounts.class */
public class TAAccounts {
    private final Map<String, TAAcc> map = new HashMap();
    public final List<TAAcc> accounts = new ArrayList();

    public void postLoad() {
        this.accounts.stream().forEach(tAAcc -> {
            this.map.put(tAAcc.name, tAAcc);
        });
    }

    public TAAcc getAccount(String str) {
        TAAcc tAAcc = this.map.get(str);
        TAAcc tAAcc2 = tAAcc;
        if (tAAcc == null) {
            tAAcc2 = new TAAcc(str);
            this.map.put(str, tAAcc2);
            this.accounts.add(tAAcc2);
        }
        return tAAcc2;
    }
}
