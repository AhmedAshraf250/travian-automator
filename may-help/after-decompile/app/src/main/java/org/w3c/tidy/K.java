package org.w3c.tidy;

import java.io.PrintWriter;
import java.text.MessageFormat;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.MissingResourceException;
import java.util.ResourceBundle;
import org.w3c.tidy.R;
import traviaut.xml.TAData;

/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/K.class */
public final class K {
    private static Date b = new Date(1096227718000L);
    public static final String a = new SimpleDateFormat("dd MMM yyyy").format(b);
    private static ResourceBundle c;
    private String d;

    private String a(int i, B b2, String str, Object[] objArr, R.a aVar) throws MissingResourceException {
        String string = c.getString(str);
        return ((b2 == null || aVar == R.a.a) ? "" : b(b2)) + (aVar == R.a.d ? c.getString("error") : aVar == R.a.c ? c.getString("warning") : "") + (objArr != null ? MessageFormat.format(string, objArr) : string);
    }

    private void b(int i, B b2, String str, Object[] objArr, R.a aVar) {
        try {
            b2.b.println(a(i, b2, str, objArr, aVar));
        } catch (MissingResourceException e) {
            b2.b.println(e.toString());
        }
    }

    private void a(PrintWriter printWriter, String str, Object[] objArr, R.a aVar) {
        try {
            printWriter.println(a(-1, null, str, objArr, aVar));
        } catch (MissingResourceException e) {
            printWriter.println(e.toString());
        }
    }

    public final void a(PrintWriter printWriter) {
        a(printWriter, "version_summary", new Object[]{b}, R.a.a);
    }

    private static String a(C c2) {
        return c2 != null ? c2.h == 5 ? "<" + c2.n + ">" : c2.h == 6 ? "</" + c2.n + ">" : c2.h == 1 ? "<!DOCTYPE>" : c2.h == 4 ? "plain text" : c2.n : "";
    }

    public static void a(String str) {
        try {
            System.err.println(MessageFormat.format(c.getString("unknown_option"), str));
        } catch (MissingResourceException e) {
            System.err.println(e.toString());
        }
    }

    public static void a(String str, String str2) {
        try {
            System.err.println(MessageFormat.format(c.getString("bad_argument"), str2, str));
        } catch (MissingResourceException e) {
            System.err.println(e.toString());
        }
    }

    private String b(B b2) {
        try {
            boolean z = b2.z.Y;
            return MessageFormat.format(c.getString("line_column"), new Integer(b2.i), new Integer(b2.j));
        } catch (MissingResourceException e) {
            b2.b.println(e.toString());
            return "";
        }
    }

    public final void a(B b2, int i, int i2) {
        b2.g = (short) (b2.g + 1);
        if (b2.h <= b2.z.ah && b2.z.k) {
            String hexString = Integer.toHexString(i2);
            if ((i & (-2)) == 80) {
                b2.e = (short) (b2.e | 80);
                b(i, b2, "encoding_mismatch", new Object[]{b2.z.b(), H.d.a((String) null, new Integer(i2), b2.z)}, R.a.c);
                return;
            }
            if ((i & (-2)) == 76) {
                b2.e = (short) (b2.e | 76);
                b(i, b2, "invalid_char", new Object[]{new Integer(i & 1), hexString}, R.a.c);
                return;
            }
            if ((i & (-2)) == 77) {
                b2.e = (short) (b2.e | 77);
                b(i, b2, "invalid_char", new Object[]{new Integer(i & 1), hexString}, R.a.c);
                return;
            }
            if ((i & (-2)) == 78) {
                b2.e = (short) (b2.e | 78);
                b(i, b2, "invalid_utf8", new Object[]{new Integer(i & 1), hexString}, R.a.c);
            } else if ((i & (-2)) == 79) {
                b2.e = (short) (b2.e | 79);
                b(i, b2, "invalid_utf16", new Object[]{new Integer(i & 1), hexString}, R.a.c);
            } else if ((i & (-2)) == 82) {
                b2.e = (short) (b2.e | 82);
                b(i, b2, "invalid_ncr", new Object[]{new Integer(i & 1), hexString}, R.a.c);
            }
        }
    }

    public final void a(B b2, short s, String str) {
        b2.g = (short) (b2.g + 1);
        if (b2.h <= b2.z.ah && b2.z.k) {
            switch (s) {
                case 1:
                    b(s, b2, "missing_semicolon", new Object[]{str}, R.a.c);
                    break;
                case 2:
                    b(s, b2, "missing_semicolon_ncr", new Object[]{str}, R.a.c);
                    break;
                case 3:
                    b(s, b2, "unknown_entity", new Object[]{str}, R.a.c);
                    break;
                case TAData.ACT_VER /* 4 */:
                    b(s, b2, "unescaped_ampersand", null, R.a.c);
                    break;
                case 5:
                    b(s, b2, "apos_undefined", null, R.a.c);
                    break;
            }
        }
    }

    public final void a(B b2, C c2, C0003b c0003b, short s) {
        if (s == 52) {
            b2.h = (short) (b2.h + 1);
        } else {
            b2.g = (short) (b2.g + 1);
        }
        if (b2.h > b2.z.ah) {
        }
        if (s == 52) {
            b(s, b2, "unexpected_gt", new Object[]{a(c2)}, R.a.d);
        }
        if (b2.z.k) {
            switch (s) {
                case 36:
                    b2.i = b2.a.b();
                    b2.j = b2.a.a();
                    b(s, b2, "unexpected_end_of_file", new Object[]{a(c2)}, R.a.c);
                    break;
                case 48:
                    b(s, b2, "unknown_attribute", new Object[]{c0003b.f}, R.a.c);
                    break;
                case 49:
                    b(s, b2, "missing_attribute", new Object[]{a(c2), c0003b.f}, R.a.c);
                    break;
                case 50:
                    b(s, b2, "missing_attr_value", new Object[]{a(c2), c0003b.f}, R.a.c);
                    break;
                case 51:
                    b(s, b2, "bad_attribute_value", new Object[]{a(c2), c0003b.f, c0003b.g}, R.a.c);
                    break;
                case 53:
                    b(s, b2, "proprietary_attribute", new Object[]{a(c2), c0003b.f}, R.a.c);
                    break;
                case 54:
                    b(s, b2, "proprietary_attr_value", new Object[]{a(c2), c0003b.g}, R.a.c);
                    break;
                case 55:
                    b(s, b2, "repeated_attribute", new Object[]{a(c2), c0003b.g, c0003b.f}, R.a.c);
                    break;
                case 56:
                    b(s, b2, "missing_imagemap", new Object[]{a(c2)}, R.a.c);
                    b2.c = (short) (b2.c | 8);
                    break;
                case 57:
                    b(s, b2, "xml_attribute_value", new Object[]{a(c2), c0003b.f}, R.a.c);
                    break;
                case 58:
                    b(s, b2, "missing_quotemark", new Object[]{a(c2)}, R.a.c);
                    break;
                case 59:
                    b(s, b2, "unexpected_quotemark", new Object[]{a(c2)}, R.a.c);
                    break;
                case 60:
                    b(s, b2, "id_name_mismatch", new Object[]{a(c2)}, R.a.c);
                    break;
                case 61:
                    b(s, b2, "backslash_in_uri", new Object[]{a(c2)}, R.a.c);
                    break;
                case 62:
                    b(s, b2, "fixed_backslash", new Object[]{a(c2)}, R.a.c);
                    break;
                case 63:
                    b(s, b2, "illegal_uri_reference", new Object[]{a(c2)}, R.a.c);
                    break;
                case 64:
                    b(s, b2, "escaped_illegal_uri", new Object[]{a(c2)}, R.a.c);
                    break;
                case 65:
                    b(s, b2, "newline_in_uri", new Object[]{a(c2)}, R.a.c);
                    break;
                case 66:
                    b(s, b2, "anchor_not_unique", new Object[]{a(c2), c0003b.g}, R.a.c);
                    break;
                case 67:
                    b(s, b2, "entity_in_id", null, R.a.c);
                    break;
                case 68:
                    b(s, b2, "joining_attribute", new Object[]{a(c2), c0003b.f}, R.a.c);
                    break;
                case 69:
                    b(s, b2, "expected_equalsign", new Object[]{a(c2)}, R.a.c);
                    break;
                case 70:
                    b(s, b2, "attr_value_not_lcase", new Object[]{a(c2), c0003b.g, c0003b.f}, R.a.c);
                    break;
                case 71:
                    b(s, b2, "xml_id_sintax", new Object[]{a(c2), c0003b.f}, R.a.c);
                    break;
            }
        }
    }

    public final void a(B b2, C c2, C c3, short s) {
        Q q = b2.z.ap;
        if (s != 8 || b2.f == 0) {
            b2.g = (short) (b2.g + 1);
        }
        if (b2.h > b2.z.ah) {
            return;
        }
        if (b2.z.k) {
            switch (s) {
                case 6:
                    b(s, b2, "missing_endtag_for", new Object[]{c2.n}, R.a.c);
                    break;
                case 7:
                    b(s, b2, "missing_endtag_before", new Object[]{c2.n, a(c3)}, R.a.c);
                    break;
                case 8:
                    if (b2.f == 0) {
                        b(s, b2, "discarding_unexpected", new Object[]{a(c3)}, R.a.c);
                    }
                    break;
                case 9:
                    b(s, b2, "nested_emphasis", new Object[]{a(c3)}, R.a.b);
                    break;
                case 10:
                    b(s, b2, "non_matching_endtag", new Object[]{a(c3), c2.n}, R.a.c);
                    break;
                case 11:
                    b(s, b2, "tag_not_allowed_in", new Object[]{a(c3), c2.n}, R.a.c);
                    break;
                case 12:
                    b(s, b2, "missing_starttag", new Object[]{c3.n}, R.a.c);
                    break;
                case 13:
                    if (c2 == null) {
                        b(s, b2, "unexpected_endtag", new Object[]{c3.n}, R.a.c);
                    } else {
                        b(s, b2, "unexpected_endtag_in", new Object[]{c3.n, c2.n}, R.a.c);
                    }
                    break;
                case 14:
                    b(s, b2, "using_br_inplace_of", new Object[]{a(c3)}, R.a.c);
                    break;
                case 15:
                    b(s, b2, "inserting_tag", new Object[]{c3.n}, R.a.c);
                    break;
                case 17:
                    b(s, b2, "missing_title_element", null, R.a.c);
                    break;
                case 19:
                    b(s, b2, "cant_be_nested", new Object[]{a(c3)}, R.a.c);
                    break;
                case 20:
                    if (c2.m == null || (c2.m.c & 524288) == 0) {
                        b(s, b2, "replacing_element", new Object[]{a(c2), a(c3)}, R.a.c);
                    } else {
                        b(s, b2, "obsolete_element", new Object[]{a(c2), a(c3)}, R.a.c);
                    }
                    break;
                case 21:
                    b(s, b2, "proprietary_element", new Object[]{a(c3)}, R.a.c);
                    if (c3.m == q.U) {
                        b2.d = (short) (b2.d | 2);
                    } else if (c3.m == q.T) {
                        b2.d = (short) (b2.d | 1);
                    } else if (c3.m == q.Q) {
                        b2.d = (short) (b2.d | 4);
                    }
                    break;
                case 23:
                    b(s, b2, "trim_empty_element", new Object[]{a(c2)}, R.a.c);
                    break;
                case 24:
                    b(s, b2, "coerce_to_endtag", new Object[]{c2.n}, R.a.b);
                    break;
                case 25:
                    b(s, b2, "illegal_nesting", new Object[]{a(c2)}, R.a.c);
                    break;
                case 26:
                    b(s, b2, "noframes_content", new Object[]{a(c3)}, R.a.c);
                    break;
                case 27:
                    b(s, b2, "content_after_body", null, R.a.c);
                    break;
                case 28:
                    b(s, b2, "inconsistent_version", null, R.a.c);
                    break;
                case 29:
                    b(s, b2, "malformed_comment", null, R.a.c);
                    break;
                case 30:
                    b(s, b2, "bad_comment_chars", null, R.a.c);
                    break;
                case 31:
                    b(s, b2, "bad_xml_comment", null, R.a.c);
                    break;
                case 32:
                    b(s, b2, "bad_cdata_content", null, R.a.c);
                    break;
                case 33:
                    b(s, b2, "inconsistent_namespace", null, R.a.c);
                    break;
                case 34:
                    b(s, b2, "doctype_after_tags", null, R.a.c);
                    break;
                case 35:
                    b(s, b2, "malformed_doctype", null, R.a.c);
                    break;
                case 36:
                    b2.i = b2.a.b();
                    b2.j = b2.a.a();
                    b(s, b2, "unexpected_end_of_file", new Object[]{a(c2)}, R.a.c);
                    break;
                case 37:
                    b(s, b2, "dtype_not_upper_case", null, R.a.c);
                    break;
                case 38:
                    if (c2 == null) {
                        b(s, b2, "too_many_elements", new Object[]{c3.n}, R.a.c);
                    } else {
                        b(s, b2, "too_many_elements_in", new Object[]{c3.n, c2.n}, R.a.c);
                    }
                    break;
                case 39:
                    b(s, b2, "unescaped_element", new Object[]{a(c2)}, R.a.c);
                    break;
                case 40:
                    b(s, b2, "nested_quotation", null, R.a.c);
                    break;
                case 41:
                    b(s, b2, "element_not_empty", new Object[]{a(c2)}, R.a.c);
                    break;
                case 44:
                    b(s, b2, "missing_doctype", null, R.a.c);
                    break;
            }
        }
        if (s != 8 || b2.f == 0) {
            return;
        }
        b(s, b2, "discarding_unexpected", new Object[]{a(c3)}, R.a.d);
    }

    public final void b(B b2, C c2, C c3, short s) {
        b2.h = (short) (b2.h + 1);
        if (b2.h > b2.z.ah) {
            return;
        }
        if (s == 16) {
            b(s, b2, "suspected_missing_quote", null, R.a.d);
            return;
        }
        if (s == 18) {
            b(s, b2, "duplicate_frameset", null, R.a.d);
            return;
        }
        if (s == 22) {
            b(s, b2, "unknown_element", new Object[]{a(c3)}, R.a.d);
        } else if (s == 13) {
            if (c2 != null) {
                b(s, b2, "unexpected_endtag_in", new Object[]{c3.n, c2.n}, R.a.d);
            } else {
                b(s, b2, "unexpected_endtag", new Object[]{c3.n}, R.a.d);
            }
        }
    }

    public final void a(B b2) {
        if ((b2.c & 48) != 0 && ((b2.c & 16) == 0 || (b2.c & 32) != 0)) {
            b2.c = (short) (b2.c & (-49));
        }
        if (b2.e != 0) {
            if ((b2.e & 76) != 0) {
                int i = 0;
                if ("Cp1252".equals(b2.z.b())) {
                    i = 1;
                } else if ("MacRoman".equals(b2.z.b())) {
                    i = 2;
                }
                b(76, b2, "vendor_specific_chars_summary", new Object[]{new Integer(i)}, R.a.a);
            }
            if ((b2.e & 77) != 0 || (b2.e & 82) != 0) {
                int i2 = 0;
                if ("Cp1252".equals(b2.z.b())) {
                    i2 = 1;
                } else if ("MacRoman".equals(b2.z.b())) {
                    i2 = 2;
                }
                b(77, b2, "invalid_sgml_chars_summary", new Object[]{new Integer(i2)}, R.a.a);
            }
            if ((b2.e & 78) != 0) {
                b(78, b2, "invalid_utf8_summary", null, R.a.a);
            }
            if ((b2.e & 79) != 0) {
                b(79, b2, "invalid_utf16_summary", null, R.a.a);
            }
            if ((b2.e & 81) != 0) {
                b(81, b2, "invaliduri_summary", null, R.a.a);
            }
        }
        if (b2.f != 0) {
            b(113, b2, "badform_summary", null, R.a.a);
        }
        if (b2.c != 0) {
            if ((b2.c & 4) != 0) {
                b(4, b2, "badaccess_missing_summary", null, R.a.a);
            }
            if ((b2.c & 1) != 0) {
                b(1, b2, "badaccess_missing_image_alt", null, R.a.a);
            }
            if ((b2.c & 8) != 0) {
                b(8, b2, "badaccess_missing_image_map", null, R.a.a);
            }
            if ((b2.c & 2) != 0) {
                b(2, b2, "badaccess_missing_link_alt", null, R.a.a);
            }
            if ((b2.c & 16) != 0 && (b2.c & 32) == 0) {
                b(16, b2, "badaccess_frames", null, R.a.a);
            }
            b(112, b2, "badaccess_summary", new Object[]{"http://www.w3.org/WAI/GL"}, R.a.a);
        }
        if (b2.d != 0) {
            if ((b2.d & 2) != 0) {
                b(2, b2, "badlayout_using_layer", null, R.a.a);
            }
            if ((b2.d & 1) != 0) {
                b(1, b2, "badlayout_using_spacer", null, R.a.a);
            }
            if ((b2.d & 8) != 0) {
                b(8, b2, "badlayout_using_font", null, R.a.a);
            }
            if ((b2.d & 4) != 0) {
                b(4, b2, "badlayout_using_nobr", null, R.a.a);
            }
            if ((b2.d & 16) != 0) {
                b(16, b2, "badlayout_using_body", null, R.a.a);
            }
        }
    }

    public final void a(PrintWriter printWriter, char c2) {
        a(printWriter, "unrecognized_option", new Object[]{new String(new char[]{c2})}, R.a.d);
    }

    public final void a(PrintWriter printWriter, String str) {
        a(printWriter, "unknown_file", new Object[]{"Tidy", str}, R.a.d);
    }

    public final void b(PrintWriter printWriter) {
        a(printWriter, "needs_author_intervention", (Object[]) null, R.a.a);
    }

    public final void c(PrintWriter printWriter) {
        a(printWriter, "general_info", (Object[]) null, R.a.a);
    }

    public final void d(PrintWriter printWriter) {
        a(printWriter, "hello_message", new Object[]{b, this.d}, R.a.a);
    }

    public final void b(String str) {
        this.d = str;
    }

    public final void a(B b2, String str, C c2) {
        int i = 0;
        Object objB = b2.b();
        int[] iArr = new int[1];
        b2.i = 1;
        b2.j = 1;
        if (c2 != null) {
            StringBuffer stringBuffer = new StringBuffer();
            int iA = c2.e;
            while (iA < c2.f) {
                byte b3 = c2.g[iA];
                int i2 = b3;
                if (b3 < 0) {
                    iA += F.a(c2.g, iA, iArr);
                    i2 = iArr[0];
                }
                if (i2 == 34) {
                    i++;
                } else if (i == 1) {
                    stringBuffer.append((char) i2);
                }
                iA++;
            }
            b(110, b2, "doctype_given", new Object[]{str, stringBuffer}, R.a.a);
        }
        Object[] objArr = new Object[2];
        objArr[0] = str;
        objArr[1] = objB != null ? objB : "HTML proprietary";
        b(111, b2, "report_version", objArr, R.a.a);
    }

    public final void a(PrintWriter printWriter, B b2) {
        if (b2.g > 0 || b2.h > 0) {
            a(printWriter, "num_warnings", new Object[]{new Integer(b2.g), new Integer(b2.h)}, R.a.a);
        } else {
            a(printWriter, "no_warnings", (Object[]) null, R.a.a);
        }
    }

    public final void e(PrintWriter printWriter) {
        a(printWriter, "help_text", new Object[]{"Tidy", b}, R.a.a);
    }

    public final void f(PrintWriter printWriter) {
        a(printWriter, "bad_tree", (Object[]) null, R.a.d);
    }

    static {
        try {
            c = ResourceBundle.getBundle("org/w3c/tidy/TidyMessages");
        } catch (MissingResourceException e) {
            throw new Error(e.toString());
        }
    }
}
