package traviaut.xml;

import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

/* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TAHero.class */
public class TAHero {
    public boolean exps;
    public int expPower;
    public int expOff;
    public int expDef;
    public int expResources;
    public int expRegen;
    public boolean togold;
    public boolean inherit = true;
    public boolean adventures = true;
    public int herohealth = 40;
    public boolean revive = true;
    public int silverres = 500;
    public boolean auctionsell = true;
    public List<TAHeroItem> items = new ArrayList();
    private Map<Integer, TAHeroItem> itmap = new LinkedHashMap();

    public int[] getExps() {
        return new int[]{this.expPower, this.expOff, this.expDef, this.expResources, this.expRegen};
    }

    public boolean expsEnabled() {
        if (!this.exps) {
            return false;
        }
        for (int i : getExps()) {
            if (i > 0) {
                return true;
            }
        }
        return false;
    }

    public int getGoldEx(int i) {
        if (this.togold) {
            return (i - this.silverres) / 200;
        }
        return 0;
    }

    public void fillMap() {
        this.items.stream().forEach(tAHeroItem -> {
            this.itmap.put(Integer.valueOf(tAHeroItem.id), tAHeroItem);
        });
    }

    public TAHeroItem getItem(int i) {
        if (!this.itmap.containsKey(Integer.valueOf(i))) {
            TAHeroItem tAHeroItem = new TAHeroItem(i);
            this.items.add(tAHeroItem);
            this.itmap.put(Integer.valueOf(i), tAHeroItem);
        }
        return this.itmap.get(Integer.valueOf(i));
    }
}
