package org.w3c.tidy;

/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/R.class */
public final class R {

    /* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/R$a.class */
    public static final class a implements Comparable {
        public static final a a = new a(0);
        public static final a b = new a(1);
        public static final a c = new a(2);
        public static final a d = new a(3);
        private short e;

        private a(int i) {
            this.e = (short) i;
        }

        @Override // java.lang.Comparable
        public final int compareTo(Object obj) {
            return this.e - ((a) obj).e;
        }

        public final boolean equals(Object obj) {
            return (obj instanceof a) && this.e == ((a) obj).e;
        }

        public final String toString() {
            switch (this.e) {
                case 0:
                    return "SUMMARY";
                case 1:
                    return "INFO";
                case 2:
                    return "WARNING";
                case 3:
                    return "ERROR";
                default:
                    return "?";
            }
        }

        public final int hashCode() {
            return super.hashCode();
        }
    }
}
