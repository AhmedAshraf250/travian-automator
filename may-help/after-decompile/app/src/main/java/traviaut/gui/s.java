package traviaut.gui;

import java.util.HashMap;
import java.util.Map;
import javax.swing.JLabel;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/s.class */
public final class s {
    private final Map<Integer, r> a = new HashMap();
    private final Map<JLabel, r> b = new HashMap();

    public final void a(int i) {
        this.a.remove(Integer.valueOf(i));
    }

    public final r a(JLabel jLabel, traviaut.d.a aVar) {
        r rVar = this.b.get(jLabel);
        int iA = aVar.a();
        r rVar2 = this.a.get(Integer.valueOf(iA));
        r rVar3 = rVar2;
        if (rVar2 == null) {
            rVar3 = new r(iA);
            this.a.put(Integer.valueOf(iA), rVar3);
        }
        if (rVar != rVar3) {
            if (rVar != null) {
                rVar.a(jLabel, this);
            }
            this.b.put(jLabel, rVar3);
        }
        rVar3.a(jLabel, aVar);
        return rVar3;
    }

    public final void a(JLabel jLabel) {
        r rVarRemove = this.b.remove(jLabel);
        if (rVarRemove != null) {
            rVarRemove.a(jLabel, this);
        }
    }
}
