package traviaut.xml;

import javax.xml.bind.annotation.XmlAttribute;
import traviaut.Main;

/* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TACeleb.class */
public class TACeleb {

    @XmlAttribute
    public CelebType type = CelebType.ANY;

    /* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TACeleb$CelebType.class */
    public enum CelebType {
        SMALL,
        GREAT,
        TOWNHALL_LEVEL,
        ANY
    }

    public boolean isAllowed(int i) {
        if (i == 0) {
            return false;
        }
        return (Main.b() && this.type == CelebType.GREAT && i < 10) ? false : true;
    }

    public boolean isAny() {
        return !Main.b() || this.type == CelebType.ANY;
    }

    public boolean isGreatWanted(int i) {
        if (!Main.b()) {
            return true;
        }
        switch (this.type) {
            case SMALL:
                return false;
            case TOWNHALL_LEVEL:
            case ANY:
                return i >= 10;
            default:
                return true;
        }
    }

    public boolean getLeastPossible(int i) {
        if (!Main.b()) {
            return false;
        }
        switch (AnonymousClass1.$SwitchMap$traviaut$xml$TACeleb$CelebType[this.type.ordinal()]) {
            case 2:
                return i >= 10;
            case TAData.ACT_VER /* 4 */:
                return true;
            default:
                return false;
        }
    }
}
