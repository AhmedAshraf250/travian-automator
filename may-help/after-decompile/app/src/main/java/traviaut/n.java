package traviaut;

/* JADX INFO: loaded from: traviaut.jar:traviaut/n.class */
public enum n {
    VER31("T3.1", "unx.js?w"),
    VER35("T3.5", "unx.js?azg"),
    VER35n("T3.5 js929", "unx.js?929"),
    VER36("T3.6 js221", "unx.js"),
    VER40("T4.0 Safari", "travian_Travian_4.0_Safari"),
    VER40a("T4.0a Banone", "travian_Travian_4.0_Banone"),
    VER42("T4.2 Himmelsstuermer", "travian_Travian_4.2_Himmelsstuermer"),
    VER42a("T4.2a BigBang", "travian_Travian_4.2_BigBang"),
    VER42_birth("T4.2a Birthday", "travian_Travian_4.2_BirthdaySpecial_Deploy"),
    VER42b("T4.2b Stuffy", "travian_Travian_4.2_StuffyStuff_Deploy"),
    VER42c("T4.2c Schmetterschling", "travian_Travian_4.2_Schmetterschling_Deploy"),
    VER42d("T4.2d TerrorBot", "travian_Travian_4.2_TerrorBot_Deploy"),
    VER42e("T4.2e UniqueCorn", "travian_Travian_4.2_UniqueCorn_Deploy"),
    VER44a("T4.4a ConFusion", "travian_Travian_4.4_ConFusion_Deploy"),
    VER44b("T4.4b Delusion", "travian_4.4delusion");

    private final String p;
    private final String q;

    n(String str, String str2) {
        this.p = str;
        this.q = str2;
    }

    public final String a() {
        return this.p;
    }
}
