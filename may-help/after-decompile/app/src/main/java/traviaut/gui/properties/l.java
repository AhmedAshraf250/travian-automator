package traviaut.gui.properties;

import traviaut.Main;
import traviaut.xml.TAHero;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/properties/l.class */
public final class l extends w {
    public l(TAHero tAHero, boolean z) {
        super(tAHero);
        if (z) {
            this.a.add(new s("inherit", "Use global settings", ""));
        }
        this.a.add(new s("adventures", "Enable hero adventures", ""));
        this.a.add(new v("herohealth", "Hero health limit:", "no adventures if health of hero is below this limit", 0, 100, 5));
        this.a.add(new s("revive", "Revive dead hero", ""));
        this.a.add(new s("exps", "Upgrade hero atributes (set TOTAL points ratio here!)", "set total points target ratio here!"));
        this.a.add(new v("expPower", "Fighting strength:", "target ratio of power", 0, 100, 1));
        this.a.add(new v("expOff", "Off bonus:", "target ratio of off bonus", 0, 100, 1));
        this.a.add(new v("expDef", "Def bonus:", "target ratio of def bonus", 0, 100, 1));
        this.a.add(new v("expResources", "Resources:", "target ratio of resources", 0, 100, 1));
        this.a.add(new v("expRegen", "Regeneration:", "target ratio of regeneration", 0, 100, 1));
        s sVar = new s("togold", "Exchange silver to gold", "");
        this.a.add(sVar);
        v vVar = new v("silverres", "Keep silver reserve", "", 0, 100000, 100);
        this.a.add(vVar);
        if (!Main.b()) {
            sVar.a();
            vVar.a();
        }
        if (Main.b() && z) {
            this.a.add(new s("auctionsell", "Sell hero items", ""));
        }
    }

    @Override // traviaut.gui.properties.u
    public final String a() {
        return "Hero";
    }
}
