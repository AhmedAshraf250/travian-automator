package traviaut.gui;

import java.awt.GridBagLayout;
import javax.swing.JButton;
import javax.swing.JDialog;
import javax.swing.JFrame;
import javax.swing.JLabel;

/* JADX INFO: loaded from: traviaut.jar:traviaut/gui/a.class */
public final class a extends JDialog {
    public a(JFrame jFrame) {
        super(jFrame, true);
        setDefaultCloseOperation(2);
        setTitle("About Travian Automator");
        getContentPane().setLayout(new GridBagLayout());
        add(new JLabel(("<html>TraviAut - Travian Automator<br>Version: 20161021.0-tap<br>") + "Copyright © 2007-2016 Ondrej Preclik</html>"), new c(0, 0).b().b(10).a);
        add(new h("http://traviaut.com"), new c(0, 1).a);
        add(new JLabel("<html><br>Uses JTidy for html parsing</html>"), new c(0, 2).b(10).b().a);
        add(new h("http://jtidy.sourceforge.net"), new c(0, 3).a);
        add(new JLabel("Java version: " + traviaut.g.a), new c(0, 4).b(10).b().a);
        JButton jButton = new JButton("Close");
        jButton.addActionListener(actionEvent -> {
            dispose();
        });
        getContentPane().add(jButton, new c(0, 5).b(10).a);
        pack();
        setLocationRelativeTo(jFrame);
    }
}
