package traviaut.gui;

import java.awt.Frame;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import javax.swing.JButton;
import javax.swing.JDialog;
import javax.swing.JSpinner;
import javax.swing.SpinnerNumberModel;
import traviaut.xml.TAVillage;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/o.class */
public final class o extends JDialog {
    private final TAVillage a;
    private JButton b;
    private JSpinner c;

    public o(Frame frame, TAVillage tAVillage) {
        super(frame, true);
        this.b = new JButton();
        this.c = new JSpinner();
        setDefaultCloseOperation(2);
        setTitle("ID to demolish");
        this.b.setText("close");
        this.b.addActionListener(new ActionListener() { // from class: traviaut.gui.o.1
            public final void actionPerformed(ActionEvent actionEvent) {
                o.a(o.this, actionEvent);
            }
        });
        getContentPane().add(this.b, "South");
        this.c.setModel(new SpinnerNumberModel(0, 0, 42, 1));
        this.c.setName("");
        getContentPane().add(this.c, "Center");
        pack();
        this.a = tAVillage;
        this.c.setValue(Integer.valueOf(this.a.builder.demolishID));
        setLocationRelativeTo(frame);
    }

    static /* synthetic */ void a(o oVar, ActionEvent actionEvent) {
        oVar.a.builder.demolishID = ((Integer) oVar.c.getValue()).intValue();
        traviaut.f.a();
        oVar.dispose();
    }
}
