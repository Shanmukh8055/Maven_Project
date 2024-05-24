package com.areport.dpmxbrl.Config;

import java.util.Arrays;
import java.util.HashMap;
import java.util.Map;
import java.util.Set;
import java.util.TreeSet;
import java.util.List;
import java.util.ArrayList;


public class Config {

    public static String monetaryItem = "EUR";
    public static String imagePath = "\\images.logo.svg\\";
    public static String DIRECTORY = "\\";

   public static Set<?> lang = new TreeSet<>(Arrays.asList("en","bs-Latn-BA","ba"));
  public static Set<?> conf = new TreeSet<>(Arrays.asList( "lab-codes","rend","def","def","tab"));


public static Set<?> module = new TreeSet<>(Arrays.asList("pre","rend","lab-codes"));

public static Set<?> createinstance = new TreeSet<>(Arrays.asList("rend","def"));


    public static String owner = "www.eba.europa.eu";

    public static String path = "app/public/tax/";

    public static String publicDir() {
        return path;
    }

    public static String prefixOwner = "fba";

    public static String getLogoPath(String logo){
        return DIRECTORY+".images"+".logo.svg";
    }


    public static List<Map<String,String>> owners()
    {
        ArrayList<Map<String, String>> ownerList = new ArrayList<Map<String, String>>();
         Map<String,String> fba = new HashMap<String, String>();
        Map<String,String> eba = new HashMap<String, String>();
        Map<String,String> audt = new HashMap<String, String>();
        fba.put("namespace","http://ww.fba.ba");
        fba.put("officialLocation","http://www.fba.ba/xbrl");
        fba.put("prefix","fba");
        fba.put("copyright","(C) FBA");

        eba.put("namespace","http://www.eba.europa.eu/xbrl/crr");
        eba.put("officialLocation","http://www.eba.europa.eu/eu/fr/xbrl/crr");
        eba.put("prefix","eba");
        eba.put("copyright","(C) EBA");

        audt.put("namespace","http://www.auditchain.finance/");
        audt.put("officialLocation","http://www.auditchain.finance/");
        audt.put("prefix","audt");
        audt.put("copyright","(C) Auditchain");

        ownerList.add(fba);
        ownerList.add(eba);
        ownerList.add(audt);
        

        return ownerList;
    }
        /* 
        return [
            'fba' => [
                'namespace' => 'http://www.fba.ba',
                'officialLocation' => 'http://www.fba.ba/xbrl',
                'prefix' => 'fba',
                'copyright' => '(C) FBA'
            ],
            'eba' => [
                'namespace' => 'http://www.eba.europa.eu/xbrl/crr',
                'officialLocation' => 'http://www.eba.europa.eu/eu/fr/xbrl/crr',
                'prefix' => 'eba',
                'copyright' => '(C) EBA'
            ],
            'audt' => [
                'namespace' => 'http://www.auditchain.finance/',
                'officialLocation' => 'http://www.auditchain.finance/fr/dpm',
                'prefix' => 'audt',
                'copyright' => '(C) Auditchain'
            ],
        ];
    }
    */

    public static String getTmpPdfDir(String logo){
        return DIRECTORY+".images"+".logo.svg";
    }
}
