package com.areport.dpmxbrl;

import com.areport.dpmxbrl.Config.Config;

import junit.framework.Test;
import junit.framework.TestCase;
import junit.framework.TestSuite;

/**
 * Unit test for simple App.
 */
public class AppTest 
    extends TestCase
{
    /**
     * Create the test case
     *
     * @param testName name of the test case
     */
    public AppTest( String testName )
    {
        super( testName );
    }

    /**
     * @return the suite of tests being tested
     */
    public static Test suite()
    {
        return new TestSuite( AppTest.class );
    }

    /**
     * Rigourous Test :-)
     */
    public void testApp()
    {
        Config c = new Config();

        assertTrue(c.DIRECTORY.equals("\\"));
        assertFalse(c.DIRECTORY.equals("Example"));

        assertTrue( c.monetaryItem.equals("EUR"));
        assertFalse(c.monetaryItem.equals("Ex"));

        assertTrue( c.imagePath .equals("images.logo.svg"));
        assertFalse(c.imagePath .equals("Exam"));

        assertTrue( c.publicDir().equals("app/public/tax/"));
        assertFalse( c.publicDir().equals("app/public"));
    }
}
