package org.w3c.tidy;

import java.util.HashMap;
import java.util.Map;

/* JADX INFO: loaded from: traviaut.jar:org/w3c/tidy/w.class */
public abstract class w {
    private static Map<String, String[]> a;

    static {
        HashMap map = new HashMap();
        a = map;
        map.put("ISO-8859-1", new String[]{"ISO-8859-1", "ISO8859_1"});
        a.put("ISO8859_1", new String[]{"ISO-8859-1", "ISO8859_1"});
        a.put("ISO-IR-100", new String[]{"ISO-8859-1", "ISO8859_1"});
        a.put("LATIN1", new String[]{"ISO-8859-1", "ISO8859_1"});
        a.put("CSISOLATIN1", new String[]{"ISO-8859-1", "ISO8859_1"});
        a.put("L1", new String[]{"ISO-8859-1", "ISO8859_1"});
        a.put("819", new String[]{"ISO-8859-1", "ISO8859_1"});
        a.put("US-ASCII", new String[]{"US-ASCII", "ASCII"});
        a.put("ASCII", new String[]{"US-ASCII", "ASCII"});
        a.put("ISO-IR-6", new String[]{"US-ASCII", "ASCII"});
        a.put("CSASCII", new String[]{"US-ASCII", "ASCII"});
        a.put("ISO646-US", new String[]{"US-ASCII", "ASCII"});
        a.put("US", new String[]{"US-ASCII", "ASCII"});
        a.put("367", new String[]{"US-ASCII", "ASCII"});
        a.put("UTF-8", new String[]{"UTF-8", "UTF8"});
        a.put("UTF8", new String[]{"UTF-8", "UTF8"});
        a.put("UTF-16", new String[]{"UTF-16", "Unicode"});
        a.put("UNICODE", new String[]{"UTF-16", "Unicode"});
        a.put("UTF16", new String[]{"UTF-16", "Unicode"});
        a.put("UTF16", new String[]{"UTF-16", "Unicode"});
        a.put("UTF-16BE", new String[]{"UTF-16BE", "UnicodeBig"});
        a.put("UNICODEBIG", new String[]{"UTF-16BE", "UnicodeBig"});
        a.put("UTF16-BE", new String[]{"UTF-16BE", "UnicodeBig"});
        a.put("UTF-16LE", new String[]{"UTF-16LE", "UnicodeLittle"});
        a.put("UNICODELITTLE", new String[]{"UTF-16LE", "UnicodeLittle"});
        a.put("UTF16-LE", new String[]{"UTF-16LE", "UnicodeLittle"});
        a.put("UTF16BE", new String[]{"UTF-16BE", "UnicodeBig"});
        a.put("UTF16LE", new String[]{"UTF-16LE", "UnicodeLittle"});
        a.put("BIG5", new String[]{"BIG5", "Big5"});
        a.put("CSBIG5", new String[]{"BIG5", "Big5"});
        a.put("SJIS", new String[]{"SHIFT_JIS", "SJIS"});
        a.put("SHIFT_JIS", new String[]{"SHIFT_JIS", "SJIS"});
        a.put("CSSHIFTJIS", new String[]{"CSSHIFTJIS", "SJIS"});
        a.put("MS_KANJI", new String[]{"MS_KANJI", "SJIS"});
        a.put("SHIFTJIS", new String[]{"SHIFT_JIS", "SJIS"});
        a.put("JIS", new String[]{"ISO-2022-JP", "JIS"});
        a.put("ISO-2022-JP", new String[]{"ISO-2022-JP", "JIS"});
        a.put("CSISO2022JP", new String[]{"CSISO2022JP", "JIS"});
        a.put("ISO2022", new String[]{"ISO-2022-JP", "JIS"});
        a.put("ISO2022KR", new String[]{"ISO-2022-KR", "ISO2022KR"});
        a.put("ISO-2022-KR", new String[]{"ISO-2022-KR", "ISO2022KR"});
        a.put("CSISO2022KR", new String[]{"CSISO2022KR", "ISO2022KR"});
        a.put("ISO-2022-CN", new String[]{"ISO-2022-CN", "ISO2022CN"});
        a.put("ISO2022CN", new String[]{"ISO-2022-CN", "ISO2022CN"});
        a.put("MACROMAN", new String[]{"macintosh", "MacRoman"});
        a.put("MACINTOSH", new String[]{"macintosh", "MacRoman"});
        a.put("MACINTOSH ROMAN", new String[]{"macintosh", "MacRoman"});
        a.put("37", new String[]{"IBM037", "CP037"});
        a.put("273", new String[]{"IBM273", "CP273"});
        a.put("277", new String[]{"IBM277", "CP277"});
        a.put("278", new String[]{"IBM278", "CP278"});
        a.put("280", new String[]{"IBM280", "CP280"});
        a.put("284", new String[]{"IBM284", "CP284"});
        a.put("285", new String[]{"IBM285", "CP285"});
        a.put("290", new String[]{"IBM290", "CP290"});
        a.put("297", new String[]{"IBM297", "CP297"});
        a.put("420", new String[]{"IBM420", "CP420"});
        a.put("424", new String[]{"IBM424", "CP424"});
        a.put("437", new String[]{"IBM437", "CP437"});
        a.put("500", new String[]{"IBM500", "CP500"});
        a.put("775", new String[]{"IBM775", "CP775"});
        a.put("850", new String[]{"IBM850", "CP850"});
        a.put("852", new String[]{"IBM852", "CP852"});
        a.put("CSPCP852", new String[]{"IBM852", "CP852"});
        a.put("855", new String[]{"IBM855", "CP855"});
        a.put("857", new String[]{"IBM857", "CP857"});
        a.put("858", new String[]{"IBM00858", "Cp858"});
        a.put("0858", new String[]{"IBM00858", "Cp858"});
        a.put("860", new String[]{"IBM860", "CP860"});
        a.put("861", new String[]{"IBM861", "CP861"});
        a.put("IS", new String[]{"IBM861", "CP861"});
        a.put("862", new String[]{"IBM862", "CP862"});
        a.put("863", new String[]{"IBM863", "CP863"});
        a.put("864", new String[]{"IBM864", "CP864"});
        a.put("865", new String[]{"IBM865", "CP865"});
        a.put("866", new String[]{"IBM866", "CP866"});
        a.put("868", new String[]{"IBM868", "CP868"});
        a.put("AR", new String[]{"IBM868", "CP868"});
        a.put("869", new String[]{"IBM869", "CP869"});
        a.put("GR", new String[]{"IBM869", "CP869"});
        a.put("870", new String[]{"IBM870", "CP870"});
        a.put("871", new String[]{"IBM871", "CP871"});
        a.put("EBCDIC-CP-IS", new String[]{"IBM871", "CP871"});
        a.put("918", new String[]{"CP918", "CP918"});
        a.put("924", new String[]{"IBM00924", "CP924"});
        a.put("0924", new String[]{"IBM00924", "CP924"});
        a.put("1026", new String[]{"IBM1026", "CP1026"});
        a.put("1047", new String[]{"IBM1047", "Cp1047"});
        a.put("1140", new String[]{"IBM01140", "Cp1140"});
        a.put("1141", new String[]{"IBM01141", "Cp1141"});
        a.put("1142", new String[]{"IBM01142", "Cp1142"});
        a.put("1143", new String[]{"IBM01143", "Cp1143"});
        a.put("1144", new String[]{"IBM01144", "Cp1144"});
        a.put("1145", new String[]{"IBM01145", "Cp1145"});
        a.put("1146", new String[]{"IBM01146", "Cp1146"});
        a.put("1147", new String[]{"IBM01147", "Cp1147"});
        a.put("1148", new String[]{"IBM01148", "Cp1148"});
        a.put("1149", new String[]{"IBM01149", "Cp1149"});
        a.put("1250", new String[]{"WINDOWS-1250", "Cp1250"});
        a.put("1251", new String[]{"WINDOWS-1251", "Cp1251"});
        a.put("1252", new String[]{"WINDOWS-1252", "Cp1252"});
        a.put("WIN1252", new String[]{"WINDOWS-1252", "Cp1252"});
        a.put("1253", new String[]{"WINDOWS-1253", "Cp1253"});
        a.put("1254", new String[]{"WINDOWS-1254", "Cp1254"});
        a.put("1255", new String[]{"WINDOWS-1255", "Cp1255"});
        a.put("1256", new String[]{"WINDOWS-1256", "Cp1256"});
        a.put("1257", new String[]{"WINDOWS-1257", "Cp1257"});
        a.put("1258", new String[]{"WINDOWS-1258", "Cp1258"});
        a.put("EUC-JP", new String[]{"EUC-JP", "EUCJIS"});
        a.put("EUCJIS", new String[]{"EUC-JP", "EUCJIS"});
        a.put("EUC-KR", new String[]{"EUC-KR", "KSC5601"});
        a.put("KSC5601", new String[]{"EUC-KR", "KSC5601"});
        a.put("GB2312", new String[]{"GB2312", "GB2312"});
        a.put("CSGB2312", new String[]{"GB2312", "GB2312"});
        a.put("X0201", new String[]{"X0201", "JIS0201"});
        a.put("JIS0201", new String[]{"X0201", "JIS0201"});
        a.put("X0208", new String[]{"X0208", "JIS0208"});
        a.put("JIS0208", new String[]{"X0208", "JIS0208"});
        a.put("ISO-IR-87", new String[]{"ISO-IR-87", "JIS0208"});
        a.put("JIS0208", new String[]{"ISO-IR-87", "JIS0208"});
        a.put("X0212", new String[]{"X0212", "JIS0212"});
        a.put("JIS0212", new String[]{"X0212", "JIS0212"});
        a.put("ISO-IR-159", new String[]{"X0212", "JIS0212"});
        a.put("GB18030", new String[]{"GB18030", "GB18030"});
        a.put("936", new String[]{"GBK", "GBK"});
        a.put("MS936", new String[]{"GBK", "GBK"});
        a.put("MS932", new String[]{"WINDOWS-31J", "MS932"});
        a.put("31J", new String[]{"WINDOWS-31J", "MS932"});
        a.put("CSWINDOWS31J", new String[]{"WINDOWS-31J", "MS932"});
        a.put("TIS-620", new String[]{"TIS-620", "TIS620"});
        a.put("TIS620", new String[]{"TIS-620", "TIS620"});
        a.put("ISO-8859-2", new String[]{"ISO-8859-2", "ISO8859_2"});
        a.put("ISO8859_2", new String[]{"ISO-8859-2", "ISO8859_2"});
        a.put("ISO-IR-101", new String[]{"ISO-8859-2", "ISO8859_2"});
        a.put("LATIN2", new String[]{"ISO-8859-2", "ISO8859_2"});
        a.put("L2", new String[]{"ISO-8859-2", "ISO8859_2"});
        a.put("ISO-8859-3", new String[]{"ISO-8859-3", "ISO8859_3"});
        a.put("ISO8859_3", new String[]{"ISO-8859-3", "ISO8859_3"});
        a.put("ISO-IR-109", new String[]{"ISO-8859-3", "ISO8859_3"});
        a.put("LATIN3", new String[]{"ISO-8859-3", "ISO8859_3"});
        a.put("L3", new String[]{"ISO-8859-3", "ISO8859_3"});
        a.put("ISO-8859-4", new String[]{"ISO-8859-4", "ISO8859_4"});
        a.put("ISO8859_4", new String[]{"ISO-8859-4", "ISO8859_4"});
        a.put("ISO-IR-110", new String[]{"ISO-8859-4", "ISO8859_4"});
        a.put("ISO-IR-110", new String[]{"ISO-8859-4", "ISO8859_4"});
        a.put("L4", new String[]{"ISO-8859-4", "ISO8859_4"});
        a.put("ISO-8859-5", new String[]{"ISO-8859-5", "ISO8859_5"});
        a.put("ISO8859_5", new String[]{"ISO-8859-5", "ISO8859_5"});
        a.put("ISO-IR-144", new String[]{"ISO-8859-5", "ISO8859_5"});
        a.put("CYRILLIC", new String[]{"ISO-8859-5", "ISO8859_5"});
        a.put("ISO-8859-6", new String[]{"ISO-8859-6", "ISO8859_6"});
        a.put("ISO8859_6", new String[]{"ISO-8859-6", "ISO8859_6"});
        a.put("ISO-IR-127", new String[]{"ISO-8859-6", "ISO8859_6"});
        a.put("ARABIC", new String[]{"ISO-8859-6", "ISO8859_6"});
        a.put("ISO-8859-7", new String[]{"ISO-8859-7", "ISO8859_7"});
        a.put("ISO8859_7", new String[]{"ISO-8859-7", "ISO8859_7"});
        a.put("ISO-IR-126", new String[]{"ISO-8859-7", "ISO8859_7"});
        a.put("GREEK", new String[]{"ISO-8859-7", "ISO8859_7"});
        a.put("ISO-8859-8", new String[]{"ISO-8859-8", "ISO8859_8"});
        a.put("ISO8859_8", new String[]{"ISO-8859-8", "ISO8859_8"});
        a.put("ISO-8859-8-I", new String[]{"ISO-8859-8", "ISO8859_8"});
        a.put("ISO-IR-138", new String[]{"ISO-8859-8", "ISO8859_8"});
        a.put("HEBREW", new String[]{"ISO-8859-8", "ISO8859_8"});
        a.put("ISO-8859-9", new String[]{"ISO-8859-9", "ISO8859_8"});
        a.put("ISO8859_8", new String[]{"ISO-8859-9", "ISO8859_8"});
        a.put("CSISOLATINHEBREW", new String[]{"ISO-8859-9", "ISO8859_9"});
        a.put("ISO-IR-148", new String[]{"ISO-8859-9", "ISO8859_9"});
        a.put("LATIN5", new String[]{"ISO-8859-9", "ISO8859_9"});
        a.put("CSISOLATIN5", new String[]{"ISO-8859-9", "ISO8859_9"});
        a.put("L5", new String[]{"ISO-8859-9", "ISO8859_9"});
        a.put("ISO-8859-15", new String[]{"ISO-8859-15", "ISO8859_15"});
        a.put("ISO8859_15", new String[]{"ISO-8859-15", "ISO8859_15"});
        a.put("KOI8-R", new String[]{"KOI8-R", "KOI8_R"});
        a.put("KOI8_R", new String[]{"CSKOI8R", "KOI8_R"});
        a.put("CSKOI8R", new String[]{"CSKOI8R", "KOI8_R"});
    }

    public static String a(String str) {
        if (str == null) {
            return null;
        }
        Map<String, String[]> map = a;
        String upperCase = str.toUpperCase();
        String strSubstring = upperCase;
        if (upperCase.startsWith("CSIBM") || strSubstring.startsWith("CCSID")) {
            strSubstring = strSubstring.substring(5);
        } else if (strSubstring.startsWith("IBM-") || strSubstring.startsWith("IBM0") || strSubstring.startsWith("CP-0")) {
            strSubstring = strSubstring.substring(4);
        } else if (strSubstring.startsWith("IBM") || strSubstring.startsWith("CP0") || strSubstring.startsWith("CP-")) {
            strSubstring = strSubstring.substring(3);
        } else if (strSubstring.startsWith("CP")) {
            strSubstring = strSubstring.substring(2);
        } else if (strSubstring.startsWith("WINDOWS-")) {
            strSubstring = strSubstring.substring(8);
        } else if (strSubstring.startsWith("ISO_")) {
            strSubstring = "ISO-" + strSubstring.substring(4);
        }
        String[] strArr = map.get(strSubstring);
        if (strArr != null) {
            return strArr[1];
        }
        return null;
    }
}
