package traviaut.xml;

import java.util.ArrayList;
import java.util.List;
import javax.xml.bind.annotation.XmlAttribute;
import javax.xml.bind.annotation.XmlElement;

/* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TABuildLayout.class */
public class TABuildLayout {

    @XmlAttribute
    public int[] resPriorities;

    @XmlElement
    private final List<TABuildPlace> building;

    public TABuildLayout() {
        this(false);
    }

    public TABuildLayout(boolean z) {
        this.resPriorities = new int[]{15, 11, 1, 1, 100};
        this.building = new ArrayList(41);
        for (int i = z ? 19 : 0; i < 41; i++) {
            this.building.add(new TABuildPlace(i));
        }
        getPlace(26).gid = 15;
        getPlace(39).gid = 16;
        getPlace(40).gid = 100;
    }

    public boolean isSet(int i) {
        return this.building.get(i).enabled;
    }

    public void set(int i, boolean z) {
        if (i < 0) {
            return;
        }
        this.building.get(i).enabled = z;
    }

    public void clean() {
        if (this.building.size() != 41 && this.building.get(0).id == 1) {
            this.building.add(0, new TABuildPlace(0));
        }
    }

    public TABuildPlace getPlace(int i) {
        return this.building.get(i - (41 - this.building.size()));
    }
}
