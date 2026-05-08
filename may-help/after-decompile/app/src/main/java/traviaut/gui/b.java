package traviaut.gui;

import java.awt.Container;
import java.awt.Dimension;
import java.awt.FlowLayout;
import java.awt.Frame;
import java.awt.event.ActionListener;
import java.util.ArrayList;
import java.util.List;
import javax.swing.JButton;
import javax.swing.JDialog;
import javax.swing.JPanel;
import javax.swing.JTabbedPane;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/b.class */
public final class b {
    private final List<traviaut.gui.properties.u> b = new ArrayList();
    public ActionListener a;

    public final void a(traviaut.gui.properties.u uVar) {
        uVar.c();
        this.b.add(uVar);
    }

    public final void a(Frame frame, String str) {
        JDialog jDialog = new JDialog(frame, str);
        jDialog.setDefaultCloseOperation(2);
        if (this.b.size() == 1) {
            traviaut.gui.properties.u uVar = this.b.get(0);
            jDialog.setTitle(uVar.a());
            jDialog.getContentPane().add(uVar.b(), "Center");
        } else {
            JTabbedPane jTabbedPane = new JTabbedPane();
            for (traviaut.gui.properties.u uVar2 : this.b) {
                jTabbedPane.addTab(uVar2.a(), uVar2.b());
            }
            jDialog.getContentPane().add(jTabbedPane, "Center");
        }
        Container contentPane = jDialog.getContentPane();
        JButton jButton = new JButton("OK");
        if (this.a != null) {
            jButton.addActionListener(this.a);
        }
        jButton.addActionListener(actionEvent -> {
            this.b.stream().forEach((v0) -> {
                v0.d();
            });
            traviaut.f.a();
            jDialog.dispose();
        });
        JButton jButton2 = new JButton("Cancel");
        jButton2.addActionListener(actionEvent2 -> {
            jDialog.dispose();
        });
        JPanel jPanel = new JPanel(new FlowLayout(1));
        jPanel.add(jButton);
        jPanel.add(jButton2);
        contentPane.add(jPanel, "South");
        jDialog.pack();
        int i = (frame.getGraphicsConfiguration().getBounds().height << 2) / 5;
        if (jDialog.getHeight() > i) {
            jDialog.setPreferredSize(new Dimension(jDialog.getWidth(), i));
            jDialog.pack();
        }
        jDialog.setLocationRelativeTo(frame);
        jDialog.setVisible(true);
    }
}
