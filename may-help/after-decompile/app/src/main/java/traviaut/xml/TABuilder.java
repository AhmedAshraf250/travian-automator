package traviaut.xml;

/* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TABuilder.class */
public class TABuilder {
    public int demolishID;
    public boolean inherit = true;
    public boolean upresources = true;
    public boolean upbuildings = true;
    public boolean newbuilds = true;
    public TABuildLayout buildings = new TABuildLayout(false);

    public boolean enabled() {
        return this.upresources || this.upbuildings;
    }

    public void clean(int i) {
        this.buildings.clean();
    }
}
