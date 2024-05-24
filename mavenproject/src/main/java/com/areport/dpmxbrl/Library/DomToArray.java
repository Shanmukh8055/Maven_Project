package com.areport.dpmxbrl.Library;

import org.w3c.dom.Document;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import java.io.File;
import java.util.ArrayList;
import java.util.HashMap;
//import java.util.List;
import java.util.Map;

public class DomToArray {

    public static Document invoke(String path) throws Exception {
        DocumentBuilderFactory dbFactory = DocumentBuilderFactory.newInstance();
        DocumentBuilder dBuilder = dbFactory.newDocumentBuilder();
        return dBuilder.parse(new File(path));
    }

    public static Map<String, Object> getArray(String path) throws Exception {
        Document dom = invoke(path);
        Node root = dom.getDocumentElement();
        Map<String, Object> output = domNodeToArray(root);
        output.put("@root", root.getNodeName());
        return output;
    }

    private static Map<String, Object> domNodeToArray(Node node) {
        Map<String, Object> output = new HashMap<>();
        switch (node.getNodeType()) {
            case Node.CDATA_SECTION_NODE:
            case Node.TEXT_NODE:
                output.put("content", node.getTextContent().trim());
                break;
            case Node.ELEMENT_NODE:
                NodeList nodeList = node.getChildNodes();
                for (int i = 0; i < nodeList.getLength(); i++) {
                    Node child = nodeList.item(i);
                   // Map<String, Object> childOutput = domNodeToArray(child);
                    String tagName = child.getNodeName();
                    if (!output.containsKey(tagName)) {
                        output.put(tagName, new ArrayList<>());
                    }
                    //((List) output.get(tagName)).add(childOutput);
                }
                if (node.hasAttributes()) {
                    Map<String, String> attributes = new HashMap<>();
                    for (int i = 0; i < node.getAttributes().getLength(); i++) {
                        Node attr = node.getAttributes().item(i);
                        attributes.put(attr.getNodeName(), attr.getNodeValue());
                    }
                    output.put("@attributes", attributes);
                }
                break;
        }
        return output;
    }

    // Other methods such as search_multdim, search_multdim_multival, getPath, build_url, strpos_arr,
    // multidimensional_arr_to_single can be implemented similarly, but I'll stop here for now.
    // If you need these methods as well, feel free to ask!
}
