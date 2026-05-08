package traviaut.xml;

import java.util.concurrent.TimeUnit;

/* JADX INFO: loaded from: traviaut.jar:traviaut/xml/TAGlobalSets.class */
public class TAGlobalSets {
    public boolean generateua;
    public boolean paused;
    public boolean checkoverflow;
    public boolean readreports;
    public boolean readmessages;
    public boolean taskaccept;
    public boolean logouten;
    public int logoutVar;
    public String userID = "";
    public String useragent = "";
    public int tradelimit = 30;
    public boolean builden = true;
    public int period = 15;
    public int bslimit = 8;
    public int tradedistlimit = 50;
    public long downtime = TimeUnit.MINUTES.toMillis(10);
    public int cropFactor = 50;
    public final TAHero hero = new TAHero();
    public boolean refreshbuild = true;
    public boolean refreshattack = true;
    public int resmaxlvl = 20;
    public boolean randperiod = true;
    public boolean settlersDefault = false;
    public int loginTime = 1;
    public int logoutTime = 1;
    public final TABuildLayout layout = new TABuildLayout(true);
    public final TACeleb celeb = new TACeleb();
    public final TATroopsTraining troopstraining = new TATroopsTraining();
    public TAMerchant merchant = new TAMerchant();
}
