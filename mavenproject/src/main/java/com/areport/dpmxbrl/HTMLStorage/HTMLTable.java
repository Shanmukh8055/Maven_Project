package com.areport.dpmxbrl.HTMLStorage;

import java.util.ArrayList;
import java.util.List;

public class HTMLTable extends HTMLCommon {

    private String autoFill = "&nbsp;";
    private boolean autoGrow = true;
    private HTMLTableStorage thead = null;
    private HTMLTableStorage tfoot = null;
    private List<HTMLTableStorage> tbodies = new ArrayList<>();
    private int tbodyCount = 0;
    private boolean useTGroups = false;

    public HTMLTable() {
        this(null, 0, false);
    }

    public HTMLTable(String attributes, int tabOffset, boolean useTGroups) {
        super(attributes, tabOffset);
        this.useTGroups = useTGroups;
        addBody(attributes);
        if (useTGroups) {
            thead = new HTMLTableStorage();
            tfoot = new HTMLTableStorage();
        }
    }

    public double apiVersion() {
        return 1.7;
    }

    public HTMLTableStorage getHeader() {
        if (thead == null) {
            useTGroups = true;
            thead = new HTMLTableStorage();
            for (int i = 0; i < tbodyCount; i++) {
                tbodies.get(i).setUseTGroups(true);
            }
        }
        return thead;
    }

    public HTMLTableStorage getFooter() {
        if (tfoot == null) {
            useTGroups = true;
            tfoot = new HTMLTableStorage();
            for (int i = 0; i < tbodyCount; i++) {
                tbodies.get(i).setUseTGroups(true);
            }
        }
        return tfoot;
    }

    public HTMLTableStorage getBody(int body) {
        adjustTbodyCount(body, "getBody");
        return tbodies.get(body);
    }

    public int addBody(String attributes) {
        if (!useTGroups && tbodyCount > 0) {
            for (int i = 0; i < tbodyCount; i++) {
                tbodies.get(i).setUseTGroups(true);
            }
            useTGroups = true;
        }
        int body = tbodyCount++;
        HTMLTableStorage tbody = new HTMLTableStorage();
        tbody.setAutoFill(autoFill);
        tbody.setAttributes(attributes);
        tbodies.add(tbody);
        return body;
    }

    private void adjustTbodyCount(int body, String method) {
        if (autoGrow) {
            while (tbodyCount <= body) {
                addBody(null);
            }
        } else {
            throw new Error("Invalid body reference[" + body + "] in HTML_Table::" + method);
        }
    }

    // Rest of the methods...
}
